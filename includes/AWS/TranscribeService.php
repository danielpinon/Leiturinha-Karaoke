<?php
/**
 * Serviço AWS Transcribe – Leiturinha-Karaoke
 */

namespace LeiturinhaKaraoke\AWS;

use Aws\S3\S3Client;
use Aws\TranscribeService\TranscribeServiceClient;
use Aws\Exception\AwsException;

if (!defined('ABSPATH')) {
    exit;
}

class TranscribeService
{
    protected $s3;
    protected $transcribe;
    protected $bucket;
    protected $region;

    public function __construct($accessKey, $secretKey, $region, $bucket)
    {
        $this->bucket = $bucket;
        $this->region = $region;

        // ----------------------------
        // Cliente S3
        // ----------------------------
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region'  => $region,
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
            'http' => [
                'verify' => false // evita erro SSL em alguns ambientes
            ]
        ]);

        // ----------------------------
        // Cliente Transcribe
        // ----------------------------
        $this->transcribe = new TranscribeServiceClient([
            'version' => '2017-10-26',
            'region'  => $region,
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
            'http' => [
                'verify' => false
            ]
        ]);
    }

    /* =====================================================
     * 1. ENVIO PARA O S3
     * ===================================================== */
    public function uploadAudio($localFilePath)
    {
        if (!file_exists($localFilePath)) {
            throw new \Exception('Arquivo de áudio não encontrado.');
        }

        $key = basename($localFilePath);

        try {
            $result = $this->s3->putObject([
                'Bucket'     => $this->bucket,
                'Key'        => $key,
                'SourceFile' => $localFilePath,
            ]);

            return [
                'key'     => $key,
                'url'     => $result['ObjectURL'],
                's3_uri'  => "s3://{$this->bucket}/{$key}",
            ];

        } catch (AwsException $e) {
            throw new \Exception('Erro ao enviar para o S3: ' . $e->getMessage());
        }
    }

    /* =====================================================
     * 2. DISPARA TRANSCRIÇÃO
     * ===================================================== */
    public function startTranscription($s3Key, $language = 'pt-BR')
    {
        $jobName  = 'lk-transcribe-' . time();
        $mediaUri = "s3://{$this->bucket}/{$s3Key}";

        try {
            $this->transcribe->startTranscriptionJob([
                'TranscriptionJobName' => $jobName,
                'LanguageCode'         => $language,
                'Media' => [
                    'MediaFileUri' => $mediaUri,
                ],
                'OutputBucketName' => $this->bucket,
            ]);

            return $jobName;

        } catch (AwsException $e) {
            throw new \Exception('Erro ao iniciar transcrição: ' . $e->getMessage());
        }
    }

    /* =====================================================
     * 3. AGUARDA FINALIZAÇÃO
     * ===================================================== */
    public function waitForCompletion($jobName, $timeout = 240)
    {
        $elapsed = 0;

        do {
            // sleep(4);
            $elapsed += 4;

            $status = $this->transcribe->getTranscriptionJob([
                'TranscriptionJobName' => $jobName
            ]);

            $state = $status['TranscriptionJob']['TranscriptionJobStatus'];

            if ($state === 'FAILED') {
                throw new \Exception(
                    'Falha na transcrição: ' .
                    $status['TranscriptionJob']['FailureReason']
                );
            }

            if ($elapsed >= $timeout) {
                throw new \Exception('Timeout aguardando transcrição.');
            }

        } while ($state !== 'COMPLETED');

        return $status['TranscriptionJob']['Transcript']['TranscriptFileUri'];
    }

    /* =====================================================
     * 4. EXECUÇÃO COMPLETA
     * ===================================================== */
    public function transcribe($localFilePath, $language = 'pt-BR')
    {
        // Upload
        $upload = $this->uploadAudio($localFilePath);

        // Start job
        $jobName = $this->startTranscription($upload['key'], $language);

        // Wait
        $jsonUrl = $this->waitForCompletion($jobName);

        // Download JSON
        $json = file_get_contents($jsonUrl);
        if (!$json) {
            throw new \Exception('Não foi possível baixar o JSON da transcrição.');
        }

        $data = json_decode($json, true);

        return [
            'status'   => 'success',
            'jobName'  => $jobName,
            'texto'    => $data['results']['transcripts'][0]['transcript'] ?? '',
            'json'     => $data,
            'file'     => $jsonUrl,
            'upload'   => $upload,
        ];
    }
}
