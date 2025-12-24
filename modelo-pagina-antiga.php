<?php

# Template Name: Página - Leitura Guiada Item

get_header();

global $wpdb;

$table_name = $wpdb->prefix . 'audio_transcription';

// Obter o nome do áudio do campo personalizado
$audio_nome = get_field('digite_o_numero_do_audio');

// Normalizar o nome do áudio para busca
$audio_nome_normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $audio_nome);
$audio_nome_normalizado = preg_replace('/[^\w\s-]/u', '', $audio_nome_normalizado);
$audio_nome_normalizado = preg_replace('/\s+/', '-', $audio_nome_normalizado);
$audio_nome_normalizado = strtolower($audio_nome_normalizado);

// Buscar o áudio pelo nome normalizado
$audio_data = $wpdb->get_results(
  $wpdb->prepare("SELECT * FROM $table_name WHERE normalized_name = %s", $audio_nome_normalizado)
);

$audio_items = array();

if (!empty($audio_data)) {
  $audio = $audio_data[0];

  $id_audio = isset($audio->id) ? $audio->id : '';
  $nome_audio = isset($audio->audio_name) ? $audio->audio_name : '';
  $data_audio = isset($audio->record_date) ? $audio->record_date : '';
  $file_path = isset($audio->file_path) ? $audio->file_path : '';
  $json_audio = isset($audio->transcript_json) ? $audio->transcript_json : '';

  if (strpos($file_path, '/wp-content') === 0) {
    $url_audio = home_url($file_path);
  } else {
    $url_audio = content_url($file_path);
  }

  if (empty($url_audio)) {
    $url_audio = '';
  }

  $audio_items[] = array(
    'id' => $id_audio,
    'nome' => $nome_audio,
    'data' => $data_audio,
    'url_audio' => $url_audio,
    'json_audio' => $json_audio
  );
}

echo '<script>';
echo 'localStorage.setItem("audio_data", "' . addslashes(json_encode($audio_items)) . '");';
echo '</script>';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="<?php echo get_template_directory_uri(); ?>/assets/css/leitura-guiada.css" rel="stylesheet">

<?php include(locate_template('partials/header/navbar.php')); ?>

<style>
  :root {
    --color-player: <?php echo get_field('cor_do_menu'); ?>;
  }

  .audio-player .controls .time {
    color: <?php echo get_field('cor_do_menu'); ?>;
    filter: brightness(0.65);
  }

  .audio-player .controls .timeline .progress::after {
    content: '';
    background: <?php echo get_field('cor_do_menu'); ?>;
    filter: brightness(0.65);
  }

  .play-container {
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 50%;
  }

  .toggle-play {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
  }

  .item-grid-audio {
    cursor: pointer;
    position: relative;
  }

  .timeline {
    cursor: pointer;
  }

  .highlight {
    background-color: #FFCB18;
    font-weight: bold;
    border-radius: 4px;
    padding: 0px 2px;
    transition: all 0.3s ease;
  }

  .highlight-pause {
    background-color: #FFE066;
    font-weight: bold;
    border-radius: 4px;
    padding: 0px 2px;
    opacity: 0.7;
    animation: pulse-pause 0.5s infinite alternate;
  }

  @keyframes pulse-pause {
    from { opacity: 0.7; }
    to { opacity: 0.4; }
  }

  .transcript-container {
    position: relative;
    z-index: 1;
    max-height: 100% !important;
    overflow-y: hidden;
  }

  .transcript-container span {
    display: inline-block;
    font-size: 1.5rem;
    color: white;
    text-align: left;
    font-family: font-01;
    font-weight: lighter;
    margin: 0 5px;
    text-transform: uppercase;
  }

  /* Ocultar tags de tempo no front-end */
  .transcript-container .time-tag {
    background: none !important;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    display: inline !important;
  }

  .transcript-container .time-indicator {
    display: none !important;
  }

  #audio-container {
    position: relative;
    padding: 6rem 2rem;
    text-align: left;
    margin-top: -20px;
  }

  #audio-container::after {
    content: '';
    background-color: <?php echo get_field('cor_do_menu'); ?>;
    filter: brightness(0.65);
    z-index: 0;
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
  }
	.transcript-container .word { 
		transition: background-color .12s ease-in-out;
		/* evite font-weight aqui para não gerar reflow */
	}
	.transcript-container .word.highlight {
		background-color: rgba(255, 230, 150, .9);
		outline: 2px solid rgba(0,0,0,.05);
	}
	.transcript-container .word.highlight-pause {
		background-color: rgba(255, 200, 120, .9);
	}
	span[data-start][data-end] { font-size: 18px !important; }
	.transcript-container{
		text-align: justify;
	}
</style>

<section id="content" style="background-color: <?php echo get_field('cor_de_fundo'); ?>;">
  <div class="container">
    <div class="row align-items-center px-0 pt-4 pb-4 pt-lg-0 pb-lg-0 d-flex">
      <div class="col-lg-2 col-3">
        <a href="javascript:history.back()" class="btn-icon-arrow-left"
          style="background-color: <?php echo get_field('cor_do_menu'); ?>;">
          <div class="bg-btn-border-right-top" style="border-color: <?php echo get_field('cor_de_fundo'); ?>;"></div>
          <div class="bg-btn-border-left-bottom" style="border-color: <?php echo get_field('cor_de_fundo'); ?>;"></div>
          <img src="<?php echo get_template_directory_uri(); ?>/assets/img/icon-arrow-left.svg" alt=""
            class="img-fluid">
        </a>
      </div>
      <div class="col-lg-8 col-9 text-center">
        <h1 style="color: <?php echo get_field('cor_do_menu'); ?>; filter: brightness(0.65);">
          Leitura Guiada
        </h1>
      </div>
    </div>
    <div class="row mt-2 pt-lg-5 pt-1">
      <div class="col-lg-10 offset-lg-1">
        <div class="card-link">
          <div class="bg-card-link">
            <img class="bg-card-link-left"
              src="<?php echo get_template_directory_uri(); ?>/assets/img/guia/bg-card-left.svg"
              alt="Background card link">
            <div
              style="background: url('<?php echo get_template_directory_uri(); ?>/assets/img/guia/bg-card-middle.svg') no-repeat center / auto 100%;"
              class="bg-card-link-middle"></div>
            <img class="bg-card-link-right"
              src="<?php echo get_template_directory_uri(); ?>/assets/img/guia/bg-card-right.svg"
              alt="Background card link">
          </div>
          <div class="grid-audio">
            <div class="item-grid-audio">
              <div class="audio-player" data-audio-src="<?php echo esc_url($url_audio); ?>">
                <div class="controls">
                  <div class="play-container"
                    style="border: 5px solid <?php echo get_field('cor_do_menu'); ?>; filter: brightness(0.65);">
                    <div class="toggle-play play">
                      <i class="fas fa-play" style="color: <?php echo get_field('cor_do_menu'); ?>;"></i>
                      <i class="fas fa-pause"
                        style="color: <?php echo get_field('cor_do_menu'); ?>; display: none;"></i>
                    </div>
                  </div>
                  <div class="timeline">
                    <div class="progress"></div>
                  </div>
                  <div class="time">
                    <div class="current">0:00</div>
                    <div class="divider">/</div>
                    <div class="length"></div>
                  </div>
                  <div class="face-avatar">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/guia/face-avatar.svg"
                      alt="Face Avatar">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-8 offset-lg-2">
        <div id="audio-container">
          <h1><?php echo single_post_title(); ?></h1>
        </div>
        <?php
        $referencia = get_field('referencia_leitura_guiada');
        if (!empty($referencia)):
          ?>
          <div style="margin-top: 12px;">
            <strong>Referência:</strong> <?php echo wp_kses_post($referencia); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>	
/**
 * KARAOKE SYNC - VERSÃO REFATORADA COM BUG FIX
 * Reorganizado e otimizado, mantendo toda a lógica original
 * Com correção automática de timestamps
 * VERSÃO 31/10/2025 (fix highlight & time-tags)
 */

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  console.log('[KARAOKE SYNC] Iniciando...');

  // ==================== CONFIGURAÇÃO ====================
  var CONFIG = {
    SYNC_OFFSET: 0.00,
    PRE_ROLL: 0.10,
    POST_ROLL: 0.14,
    MIN_DUR: 0.18,
    GAP: 0.01
  };

  // ==================== ELEMENTOS DO DOM ====================
  var DOM = {
    playerEl: document.querySelector('.audio-player'),
    container: document.getElementById('audio-container'),
    audio: null,
    playBtn: null,
    playIcon: null,
    pauseIcon: null,
    timeline: null,
    progressBar: null,
    curTimeEl: null,
    durTimeEl: null,
    transcriptWrap: null
  };

  // Validações iniciais
  if (!DOM.playerEl || !DOM.container) {
    console.error('[KARAOKE SYNC] Elementos necessários não encontrados');
    return;
  }

  var audioSrc = DOM.playerEl.getAttribute('data-audio-src');
  if (!audioSrc) {
    console.error('[KARAOKE SYNC] data-audio-src não encontrado');
    return;
  }

  // ==================== INICIALIZAÇÃO DO PLAYER ====================
  DOM.audio = new Audio(audioSrc);
  window.__letrixAudio = DOM.audio;

  DOM.playBtn = DOM.playerEl.querySelector('.controls .play-container');
  DOM.playIcon = DOM.playerEl.querySelector('.fa-play');
  DOM.pauseIcon = DOM.playerEl.querySelector('.fa-pause');
  DOM.timeline = DOM.playerEl.querySelector('.timeline');
  DOM.progressBar = DOM.playerEl.querySelector('.progress');
  DOM.curTimeEl = DOM.playerEl.querySelector('.time .current');
  DOM.durTimeEl = DOM.playerEl.querySelector('.time .length');

  // ==================== FUNÇÕES UTILITÁRIAS ====================
  var Utils = {
    pad2: function(n) {
      return String(n).padStart(2, '0');
    },

    timecode: function(num) {
      if (!Number.isFinite(num) || num < 0) return '0:00';
      var s = Math.floor(num);
      var m = Math.floor(s / 60);
      return m + ':' + Utils.pad2(s % 60);
    },

    safeDuration: function() {
      return Number.isFinite(DOM.audio.duration) && DOM.audio.duration > 0 ? DOM.audio.duration : 0;
    },

    toNum: function(v) {
      if (v == null) return NaN;
      var s = String(v).replace(/\s+/g, '').replace(',', '.');
      var n = parseFloat(s);
      return Number.isFinite(n) ? n : NaN;
    },

    setTimes: function(el, s, e) {
      el.setAttribute('data-start', (s ?? 0).toFixed(3));
      el.setAttribute('data-end', (e ?? 0).toFixed(3));
    },

    norm: function(s) {
      return String(s || '').replace(/\u00A0/g, ' ').replace(/\s+/g, ' ').trim();
    }
  };

  // ==================== BUG FIXER - APLICAR TIMESTAMPS ====================
  var BugFixer = {
    extractTimestampsFromStorage: function() {
      var ls = localStorage.getItem('audio_data');
      if (!ls) return null;

      var dataArr;
      try {
        dataArr = JSON.parse(ls);
      } catch (e) {
        console.error('[KARAOKE SYNC] Erro ao parsear audio_data:', e);
        return null;
      }

      if (!Array.isArray(dataArr) || dataArr.length === 0) return null;

      var item = dataArr[0];
      if (!item || !item.json_audio) return null;

      var j;
      try {
        j = JSON.parse(item.json_audio);
      } catch (e) {
        console.error('[KARAOKE SYNC] Erro ao parsear json_audio:', e);
        return null;
      }

      return j;
    },

    applyTimestampsToWords: function() {
      var j = BugFixer.extractTimestampsFromStorage();
      if (!j) return false;

      var allWords = DOM.transcriptWrap.querySelectorAll('.word');
      if (allWords.length === 0) return false;

      // Extrair pronunciations
      var pron = [];
      if (j && j.results && j.results.items) {
        pron = j.results.items.filter(function (it) { return it.type === 'pronunciation'; });
      }

      if (pron.length === 0) return false;

      // Aplicar timestamps
      var appliedCount = 0;
      for (var i = 0; i < allWords.length && i < pron.length; i++) {
        var wordEl = allWords[i];
        var pronData = pron[i];

        var startTime = pronData.start_time;
        var endTime = pronData.end_time;

        if (startTime && endTime) {
          var start = parseFloat(startTime);
          var end = parseFloat(endTime);

          if (Number.isFinite(start) && Number.isFinite(end)) {
            wordEl.setAttribute('data-start', start.toFixed(3));
            wordEl.setAttribute('data-end', end.toFixed(3));
            appliedCount++;
          }
        }
      }

      console.log(`[KARAOKE SYNC] ✓ ${appliedCount} timestamps aplicados`);

      // Validar e corrigir
      BugFixer.validateAndFixTimestamps(allWords);

      return appliedCount > 0;
    },

    validateAndFixTimestamps: function(allWords) {
      var MIN_DUR = CONFIG.MIN_DUR;
      var MIN_GAP = CONFIG.GAP;

      // Coletar dados
      var words = [];
      for (var i = 0; i < allWords.length; i++) {
        var el = allWords[i];
        var start = parseFloat(el.getAttribute('data-start'));
        var end = parseFloat(el.getAttribute('data-end'));

        if (!Number.isFinite(start) || !Number.isFinite(end)) continue;

        words.push({
          el: el,
          start: start,
          end: end
        });
      }

      if (words.length === 0) return;

      // Ordenar
      words.sort(function (a, b) { return a.start - b.start; });

      // Corrigir duração mínima
      for (var i = 0; i < words.length; i++) {
        if (words[i].end - words[i].start < MIN_DUR) {
          words[i].end = words[i].start + MIN_DUR;
        }
      }

      // Corrigir sobreposições
      for (var i = 0; i < words.length - 1; i++) {
        var cur = words[i];
        var next = words[i + 1];

        if (cur.end + MIN_GAP > next.start) {
          next.start = cur.end + MIN_GAP;
          if (next.end - next.start < MIN_DUR) {
            next.end = next.start + MIN_DUR;
          }
        }
      }

      // Aplicar correções
      for (var i = 0; i < words.length; i++) {
        words[i].el.setAttribute('data-start', words[i].start.toFixed(3));
        words[i].el.setAttribute('data-end', words[i].end.toFixed(3));
      }

      console.log('[KARAOKE SYNC] ✓ Timestamps validados e sincronizados');
    }
  };

  // ==================== PLAYER UI ====================
  var Player = {
    init: function() {
      DOM.audio.addEventListener('loadedmetadata', Player.onLoadedMetadata);
      DOM.audio.addEventListener('ended', Player.onEnded);
      DOM.audio.addEventListener('play', Player.onPlay);
      DOM.audio.addEventListener('pause', Player.onPause);
      DOM.audio.addEventListener('seeked', Player.onSeeked);

      if (DOM.playBtn) {
        DOM.playBtn.addEventListener('click', Player.onPlayClick);
      }

      if (DOM.timeline) {
        DOM.timeline.addEventListener('click', Player.onTimelineClick);
      }
    },

    onLoadedMetadata: function() {
      if (DOM.durTimeEl) DOM.durTimeEl.textContent = Utils.timecode(Utils.safeDuration());
      if (DOM.curTimeEl) DOM.curTimeEl.textContent = Utils.timecode(0);
      DOM.audio.volume = 0.75;
      Highlight.at(0 + CONFIG.SYNC_OFFSET);
    },

    onEnded: function() {
      if (DOM.playIcon && DOM.pauseIcon) {
        DOM.playIcon.style.display = 'inline-block';
        DOM.pauseIcon.style.display = 'none';
      }
      if (DOM.curTimeEl) DOM.curTimeEl.textContent = '0:00';
      DOM.audio.currentTime = 0;
      if (DOM.progressBar) DOM.progressBar.style.width = '0%';
      Tick.stop();
      Highlight.resetCustomTiming();
      Highlight.at(0 + CONFIG.SYNC_OFFSET);
    },

    onPlayClick: function(e) {
      e.stopPropagation();
      if (DOM.audio.paused) {
        if (DOM.playIcon) DOM.playIcon.style.display = 'none';
        if (DOM.pauseIcon) DOM.pauseIcon.style.display = 'inline-block';
        DOM.audio.play();
      } else {
        if (DOM.playIcon) DOM.playIcon.style.display = 'inline-block';
        if (DOM.pauseIcon) DOM.pauseIcon.style.display = 'none';
        DOM.audio.pause();
      }
    },

    onTimelineClick: function(e) {
      e.stopPropagation();
      var r = DOM.timeline.getBoundingClientRect();
      var ratio = Math.max(0, Math.min(1, (e.clientX - r.left) / (r.width || 1)));
      var dur = Utils.safeDuration();
      var t = ratio * dur;
      DOM.audio.currentTime = t;
      Highlight.resetCustomTiming();
      Highlight.at(t + CONFIG.SYNC_OFFSET);
      if (!DOM.audio.paused) Tick.start();
    },

    onPlay: function() {
      Highlight.resetCustomTiming();
      Tick.start();
    },

    onPause: function() {
      Tick.stop();
      Highlight.resetCustomTiming();
    },

    onSeeked: function() {
      Highlight.resetCustomTiming();
      Highlight.at(DOM.audio.currentTime + CONFIG.SYNC_OFFSET);
      if (!DOM.audio.paused) Tick.start();
    },

    updateUI: function() {
      var dur = Utils.safeDuration();
      if (DOM.progressBar && dur > 0) {
        DOM.progressBar.style.width = (DOM.audio.currentTime / dur) * 100 + '%';
      }
      if (DOM.curTimeEl) DOM.curTimeEl.textContent = Utils.timecode(DOM.audio.currentTime);
    }
  };

  // ==================== CARREGAMENTO DE DADOS ====================
  var DataLoader = {
    load: function() {
      var ls = localStorage.getItem('audio_data');
      if (!ls) return null;

      var dataArr;
      try {
        dataArr = JSON.parse(ls);
      } catch (e) {
        console.error('[KARAOKE SYNC] Erro ao parsear audio_data:', e);
        return null;
      }

      if (!Array.isArray(dataArr) || dataArr.length === 0) return null;

      var item = dataArr[0];
      if (!item || !item.json_audio) return null;

      var j;
      try {
        j = JSON.parse(item.json_audio);
      } catch (e) {
        console.error('[KARAOKE SYNC] Erro ao parsear json_audio:', e);
        return null;
      }

      return j;
    }
  };

  // ==================== PROCESSAMENTO DE TAGS DE TEMPO ====================
  var TimeTags = {
    map: new Map(),
    groupCounter: 0,

    extract: function(element) {
      var timeTags = element.querySelectorAll('.time-tag, [data-reading-duration]');
      for (var i = 0; i < timeTags.length; i++) {
        var tag = timeTags[i];
        if (!tag.hasAttribute('data-time-group')) {
          TimeTags.groupCounter++;
          tag.setAttribute('data-time-group', 'tg' + TimeTags.groupCounter);
        }
        var groupId = tag.getAttribute('data-time-group');
        var duration = parseFloat(tag.getAttribute('data-reading-duration')) || 2.0;
        var pause = parseFloat(tag.getAttribute('data-pause-after')) || 0.5;

        var words = tag.querySelectorAll('.word');
        for (var k = 0; k < words.length; k++) {
          TimeTags.map.set(words[k], { duration: duration, pause: pause, isTimeTag: true, group: groupId });
          words[k].dataset.timeDur = String(duration);
          words[k].dataset.timePause = String(pause);
          words[k].dataset.timeGroup = groupId;
        }
        if (tag.classList.contains('word')) {
          TimeTags.map.set(tag, { duration: duration, pause: pause, isTimeTag: true, group: groupId });
          tag.dataset.timeDur = String(duration);
          tag.dataset.timePause = String(pause);
          tag.dataset.timeGroup = groupId;
        }
      }
    }
  };

  window.__dbg_timeTagsMap = TimeTags.map;

  // ==================== EXPLOSÃO DE SPANS ====================
  var SpanExploder = {
    explode: function(span, feedTiming) {
      var frag = document.createDocumentFragment();
      var keepStyle = span.getAttribute('style') || '';
      var isTimeTag = span.classList.contains('time-tag') || span.hasAttribute('data-reading-duration');
      var timingConfig = null;
      var groupId = null;

      if (isTimeTag) {
        timingConfig = {
          duration: parseFloat(span.getAttribute('data-reading-duration')) || 2.0,
          pause: parseFloat(span.getAttribute('data-pause-after')) || 0.5
        };
        if (!span.hasAttribute('data-time-group')) {
          TimeTags.groupCounter++;
          span.setAttribute('data-time-group', 'tg' + TimeTags.groupCounter);
        }
        groupId = span.getAttribute('data-time-group');
      }

      var lastWordEl = null;

      for (var i = 0; i < span.childNodes.length; i++) {
        var node = span.childNodes[i];

        if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'BR') {
          frag.appendChild(document.createElement('br'));
          lastWordEl = null;
          continue;
        }

        if (node.nodeType === Node.ELEMENT_NODE &&
            (node.classList.contains && (node.classList.contains('time-tag') || node.hasAttribute('data-reading-duration')))) {
          var innerFrag = SpanExploder.explode(node, feedTiming);
          frag.appendChild(innerFrag);
          lastWordEl = null;
          continue;
        }

        if (node.nodeType === Node.TEXT_NODE) {
          SpanExploder.processTextNode(node, frag, keepStyle, timingConfig, groupId, feedTiming, function(el) { lastWordEl = el; });
          continue;
        }

        if (node.nodeType === Node.ELEMENT_NODE) {
          var txt = node.textContent || '';
          if (txt) {
            var w2 = SpanExploder.createWord(keepStyle);
            w2.textContent = txt;
            var t2 = feedTiming();
            if (t2 && Number.isFinite(+t2.start) && Number.isFinite(+t2.end)) {
              w2.setAttribute('data-start', (+t2.start).toFixed(3));
              w2.setAttribute('data-end', (+t2.end).toFixed(3));
            }
            SpanExploder.applyTimeConfig(w2, timingConfig, groupId);
            frag.appendChild(w2);
            lastWordEl = w2;
          }
          continue;
        }

        if (node.textContent) {
          var w3 = SpanExploder.createWord(keepStyle);
          w3.textContent = node.textContent;
          var t3 = feedTiming();
          if (t3 && Number.isFinite(+t3.start) && Number.isFinite(+t3.end)) {
            w3.setAttribute('data-start', (+t3.start).toFixed(3));
            w3.setAttribute('data-end', (+t3.end).toFixed(3));
          }
          SpanExploder.applyTimeConfig(w3, timingConfig, groupId);
          frag.appendChild(w3);
          lastWordEl = w3;
        }
      }

      return frag;
    },

    processTextNode: function(node, frag, keepStyle, timingConfig, groupId, feedTiming, onWordCreated) {
      var text = node.textContent || '';
      var parts = text.split(/(\s+|[–—\-\.,;:!\?\(\)"'""«»])/);
      var lastWordEl = null;

      for (var p = 0; p < parts.length; p++) {
        var part = parts[p];
        if (!part) continue;

        if (/^\s+$/.test(part)) {
          frag.appendChild(document.createTextNode(part));
          continue;
        }

        if (/^[–—\-\.,;:!\?\(\)"'""«»]$/.test(part)) {
          if (lastWordEl) {
            lastWordEl.textContent += part;
          } else {
            frag.appendChild(document.createTextNode(part));
          }
          continue;
        }

        var w = SpanExploder.createWord(keepStyle);
        w.textContent = part;
        var t = feedTiming();
        if (t && Number.isFinite(+t.start) && Number.isFinite(+t.end)) {
          w.setAttribute('data-start', (+t.start).toFixed(3));
          w.setAttribute('data-end', (+t.end).toFixed(3));
        }
        SpanExploder.applyTimeConfig(w, timingConfig, groupId);
        frag.appendChild(w);
        lastWordEl = w;
        onWordCreated(w);
      }
    },

    createWord: function(style) {
      var w = document.createElement('span');
      w.className = 'word';
      if (style) w.setAttribute('style', style);
      return w;
    },

    applyTimeConfig: function(el, timingConfig, groupId) {
      if (timingConfig) {
        TimeTags.map.set(el, {
          duration: timingConfig.duration,
          pause: timingConfig.pause,
          isTimeTag: true,
          group: groupId
        });
        el.dataset.timeDur = String(timingConfig.duration);
        el.dataset.timePause = String(timingConfig.pause);
        el.dataset.timeGroup = groupId;
      }
    }
  };

  // ==================== CONSTRUÇÃO DO TRANSCRIPT ====================
  var Transcript = {
    build: function(j) {
      DOM.transcriptWrap = document.createElement('div');
      DOM.transcriptWrap.className = 'transcript-container';
      DOM.transcriptWrap.style.maxHeight = '60vh';
      DOM.transcriptWrap.style.overflowY = 'auto';
      DOM.container.appendChild(DOM.transcriptWrap);

      var pron = [];
      if (j && j.results && j.results.items) {
        pron = j.results.items.filter(function (it) { return it.type === 'pronunciation'; });
      }

      var ptr = 0;
      function nextTiming() {
        if (ptr >= pron.length) return null;
        var it = pron[ptr];
        var s = Utils.toNum(it.start_time);
        var e = Utils.toNum(it.end_time);
        if (!Number.isFinite(s) || !Number.isFinite(e)) {
          ptr++;
          return null;
        }
        ptr++;
        return { start: s, end: e };
      }

      // Montagem do transcript
      if (j.formatted_content) {
        var root = document.createElement('div');
        root.innerHTML = j.formatted_content;

        TimeTags.extract(root);

        var spans = Array.from(root.querySelectorAll('span'));
        for (var i = 0; i < spans.length; i++) {
          var span = spans[i];
          var explodedFrag = SpanExploder.explode(span, nextTiming);
          if (span.parentNode && explodedFrag) {
            span.parentNode.replaceChild(explodedFrag, span);
          }
        }

        DOM.transcriptWrap.innerHTML = '';
        while (root.firstChild) DOM.transcriptWrap.appendChild(root.firstChild);
      } else {
        Transcript.buildFromItems(j, nextTiming);
      }

      // Remove underscores soltos
      var wordsDOM = DOM.transcriptWrap.querySelectorAll('.word');
      for (var i = 0; i < wordsDOM.length; i++) {
        var w_ = wordsDOM[i];
        if ((w_.textContent || '').trim() === '_') {
          if (w_.parentNode) w_.parentNode.removeChild(w_);
        }
      }

      Transcript.applyTimeTagsFromFormattedContent(j);
      Transcript.fillMissingWordTimings();

      // ===== BUG FIX: Aplicar timestamps do JSON =====
      console.log('[KARAOKE SYNC] Aplicando bug fix...');
      BugFixer.applyTimestampsToWords();
    },

    buildFromItems: function(j, nextTiming) {
      var last = null;
      if (j.results && j.results.items) {
        for (var ii = 0; ii < j.results.items.length; ii++) {
          var it = j.results.items[ii];
          var content = (it.alternatives && it.alternatives[0] && it.alternatives[0].content) ? it.alternatives[0].content : '';

          var isPunc = /^[–—\-\.,;:!\?\(\)"'""«»]$/.test(content);
          if (!isPunc && content.length === 1) { isPunc = /[\p{P}\p{S}\p{M}]/u.test(content); }

          if (isPunc && last) {
            last.textContent += content;
            if (it.end_time) last.setAttribute('data-end', (+it.end_time).toFixed(3));
          } else {
            var w = document.createElement('span');
            w.className = 'word';
            w.textContent = content;
            if (it.start_time) w.setAttribute('data-start', (+it.start_time).toFixed(3));
            if (it.end_time) w.setAttribute('data-end', (+it.end_time).toFixed(3));
            DOM.transcriptWrap.appendChild(w);
            last = w;
          }

          var nl = it.newline || 0;
          for (var nl_i = 0; nl_i < nl; nl_i++) {
            DOM.transcriptWrap.appendChild(document.createElement('br'));
          }
        }
      }
    },

    applyTimeTagsFromFormattedContent: function(j) {
      if (!j.formatted_content) return;

      var temp = document.createElement('div');
      temp.innerHTML = j.formatted_content;
      var tagNodes = temp.querySelectorAll('.time-tag, [data-reading-duration]');
      if (!tagNodes.length) return;

      var words = Array.from(DOM.transcriptWrap.querySelectorAll('.word'));
      if (!words.length) return;

      function concatWindow(i, j) {
        var parts = [];
        for (var k = i; k <= j; k++) parts.push(words[k].textContent || '');
        return Utils.norm(parts.join(' '));
      }

      function findWindowForTagText(targetText, maxSpan) {
        var tgt = Utils.norm(targetText);
        if (!tgt) return null;

        for (var a = 0; a < words.length; a++) {
          if (Utils.norm(words[a].textContent) === tgt) return { start: a, end: a };
        }
        for (var i = 0; i < words.length; i++) {
          for (var len = 2; len <= maxSpan && i + len - 1 < words.length; len++) {
            var jx = i + len - 1;
            if (concatWindow(i, jx) === tgt) return { start: i, end: jx };
          }
        }
        return null;
      }

      function shiftFollowing(startIndex, delta) {
        if (!delta || Math.abs(delta) < 1e-6) return;
        for (var z = startIndex; z < words.length; z++) {
          var el = words[z];
          var s = Utils.toNum(el.getAttribute('data-start'));
          var e = Utils.toNum(el.getAttribute('data-end'));
          if (Number.isFinite(s) && Number.isFinite(e)) {
            Utils.setTimes(el, s + delta, e + delta);
          }
        }
      }

      tagNodes.forEach(function (tagNode) {
        var duration = parseFloat(tagNode.getAttribute('data-reading-duration')) || 2.0;
        var pause = parseFloat(tagNode.getAttribute('data-pause-after')) || 0.5;
        var tagText = tagNode.textContent || '';
        var win = findWindowForTagText(tagText, 60);
        if (!win) return;

        var si = win.start, ei = win.end;
        var baseStart = Utils.toNum(words[si].getAttribute('data-start'));
        var baseEnd = Utils.toNum(words[ei].getAttribute('data-end'));
        if (!Number.isFinite(baseStart) || !Number.isFinite(baseEnd)) return;

        var count = (ei - si + 1);
        var slice = duration / Math.max(count, 1);

        for (var k = 0; k < count; k++) {
          var el = words[si + k];
          var s = baseStart + slice * k;
          var e = s + slice;
          Utils.setTimes(el, s, e);
        }

        if (ei + 1 < words.length) {
          var nextStart = Utils.toNum(words[ei + 1].getAttribute('data-start'));
          if (Number.isFinite(nextStart)) {
            var targetNextStart = (baseStart + duration) + pause + CONFIG.GAP;
            var delta = targetNextStart - nextStart;
            if (Math.abs(delta) > 1e-6) shiftFollowing(ei + 1, delta);
          }
        }
      });

      // Normalização leve
      for (var i = 0; i < words.length - 1; i++) {
        var cur = words[i], nxt = words[i + 1];
        var cs = Utils.toNum(cur.getAttribute('data-start'));
        var ce = Utils.toNum(cur.getAttribute('data-end'));
        var ns = Utils.toNum(nxt.getAttribute('data-start'));
        if (Number.isFinite(cs) && Number.isFinite(ce) && Number.isFinite(ns) && ce > ns - 0.005) {
          Utils.setTimes(cur, cs, Math.max(cs + 0.005, ns - 0.005));
        }
      }
    },

    fillMissingWordTimings: function() {
      var dur = Utils.safeDuration();
      var words = Array.from(DOM.transcriptWrap.querySelectorAll('.word'));
      if (!words.length) return;

      var hasAnyTimed = false;
      var info = words.map(function (el, idx) {
        var s = Utils.toNum(el.getAttribute('data-start'));
        var e = Utils.toNum(el.getAttribute('data-end'));
        var ok = Number.isFinite(s) && Number.isFinite(e) && e > s;
        if (ok) hasAnyTimed = true;
        return { el: el, idx: idx, has: ok, s: ok ? s : null, e: ok ? e : null };
      });

      if (!hasAnyTimed) {
        var baseDur = dur > 0 ? dur : (words.length * (CONFIG.MIN_DUR + CONFIG.GAP));
        var slice = baseDur / Math.max(words.length, 1);
        var t0 = 0;
        for (var i = 0; i < info.length; i++) {
          var s = t0;
          var e = s + Math.max(slice - CONFIG.GAP, CONFIG.MIN_DUR);
          Utils.setTimes(info[i].el, s, e);
          info[i].has = true; info[i].s = s; info[i].e = e;
          t0 = e + CONFIG.GAP;
        }
        return;
      }

      function fillRun(fromIdx, toIdx, leftEnd, rightStart) {
        var count = toIdx - fromIdx + 1;
        var spanAvail = Number.isFinite(rightStart)
          ? Math.max(0, rightStart - leftEnd - CONFIG.GAP)
          : count * (CONFIG.MIN_DUR + CONFIG.GAP);

        var slice = spanAvail / Math.max(count, 1);
        var cursor = leftEnd + CONFIG.GAP;

        for (var k = 0; k < count; k++) {
          var s = cursor;
          var e = s + Math.max(slice - CONFIG.GAP, CONFIG.MIN_DUR);
          if (dur > 0 && e > dur + CONFIG.POST_ROLL) e = dur + CONFIG.POST_ROLL;
          Utils.setTimes(info[fromIdx + k].el, s, e);
          info[fromIdx + k].has = true; info[fromIdx + k].s = s; info[fromIdx + k].e = e;
          cursor = e + CONFIG.GAP;
        }
      }

      var firstTimed = info.find(function (x) { return x.has; });
      var firstTimedIdx = firstTimed ? firstTimed.idx : -1;
      if (firstTimedIdx > 0) {
        var rightStart = firstTimed.s;
        var leftEnd = Math.max(0, rightStart - (firstTimedIdx * (CONFIG.MIN_DUR + CONFIG.GAP)));
        fillRun(0, firstTimedIdx - 1, leftEnd - CONFIG.GAP, rightStart);
      }

      var i = 0;
      while (i < info.length) {
        if (info[i].has) { i++; continue; }
        var start = i;
        while (i < info.length && !info[i].has) i++;
        var end = i - 1;

        var leftEnd = 0;
        for (var L = start - 1; L >= 0; L--) {
          if (info[L].has) { leftEnd = info[L].e; break; }
        }
        var rightStart = NaN;
        for (var R = end + 1; R < info.length; R++) {
          if (info[R].has) { rightStart = info[R].s; break; }
        }
        fillRun(start, end, leftEnd, rightStart);
      }

      for (var k = 0; k < info.length - 1; k++) {
        var a = info[k], b = info[k + 1];
        if (!(a.has && b.has)) continue;
        if (a.e > b.s - CONFIG.GAP) {
          var mid = Math.max(a.s + CONFIG.MIN_DUR, b.s - CONFIG.GAP);
          Utils.setTimes(a.el, a.s, mid);
        }
      }
    }
  };

  // ==================== SISTEMA DE HIGHLIGHT ====================
  var Words = {
    arr: [],
    boundsLeft: [],
    boundsRight: []
  };

  window.__dbg_wordsArr = Words.arr;

  var Highlight = {
    currentHighlightTimeout: null,
    isCustomTimingActive: false,

    build: function() {
      var allWords = DOM.transcriptWrap.querySelectorAll('.word');

      for (var i = 0; i < allWords.length; i++) {
        var el = allWords[i];
        if (el.hasAttribute('data-start') && el.hasAttribute('data-end')) {
          var start = Utils.toNum(el.getAttribute('data-start'));
          var end = Utils.toNum(el.getAttribute('data-end'));
          if (Number.isFinite(start) && Number.isFinite(end)) {
            var cfg = TimeTags.map.get(el) || (function () {
              var tg = el.closest && el.closest('.time-tag,[data-reading-duration]');
              if (!tg) return null;
              return {
                isTimeTag: true,
                group: tg.getAttribute && tg.getAttribute('data-time-group'),
                duration: parseFloat(el.dataset.timeDur || (tg.getAttribute && tg.getAttribute('data-reading-duration'))) || 0,
                pause: parseFloat(el.dataset.timePause || (tg.getAttribute && tg.getAttribute('data-pause-after'))) || 0
              };
            })();

            Words.arr.push({ el: el, idx: i, start: start, end: end, timeConfig: cfg });
          }
        }
      }

      // Ordenar e garantir monotonia
      Words.arr.sort(function (a, b) { return a.start - b.start; });
      for (var i = 1; i < Words.arr.length; i++) {
        if (Words.arr[i].start < Words.arr[i-1].start + 1e-6) {
          Words.arr[i].start = Words.arr[i-1].start + 1e-6;
          if (Words.arr[i].end < Words.arr[i].start + 1e-3) Words.arr[i].end = Words.arr[i].start + 1e-3;
        }
      }

      // Normalização
      for (var i = 0; i < Words.arr.length; i++) {
        var cur = Words.arr[i];
        var grouped = !!(cur.timeConfig && cur.timeConfig.isTimeTag);
        if (!grouped && (cur.end - cur.start) < CONFIG.MIN_DUR) {
          cur.end = cur.start + CONFIG.MIN_DUR;
        }
        var nxt = (i < Words.arr.length - 1) ? Words.arr[i + 1] : null;
        if (nxt && cur.end > nxt.start - CONFIG.GAP) {
          cur.end = Math.max(cur.start + 0.01, nxt.start - CONFIG.GAP);
        }
        Utils.setTimes(cur.el, cur.start, cur.end);
      }

      // Calcular bounds
      Words.boundsLeft = new Array(Words.arr.length);
      Words.boundsRight = new Array(Words.arr.length);
      for (var i = 0; i < Words.arr.length; i++) {
        var w = Words.arr[i];
        var prevS = i > 0 ? Words.arr[i - 1].start : (w.start - CONFIG.PRE_ROLL);
        var nextS = i < Words.arr.length - 1 ? Words.arr[i + 1].start : (w.end + CONFIG.POST_ROLL);

        var left = i > 0 ? (prevS + w.start) / 2 : (w.start - CONFIG.PRE_ROLL);
        var right = i < Words.arr.length - 1 ? (w.start + nextS) / 2 : (w.end + CONFIG.POST_ROLL);

        if (w.timeConfig && w.timeConfig.isTimeTag) {
          var isLastOfGroup = !(i < Words.arr.length - 1 &&
            Words.arr[i + 1].timeConfig &&
            Words.arr[i + 1].timeConfig.group &&
            w.timeConfig.group &&
            Words.arr[i + 1].timeConfig.group === w.timeConfig.group);
          if (isLastOfGroup) right += (w.timeConfig.pause || 0);
        }

        if (i > 0) left = Math.max(left, Words.boundsLeft[i-1] + 1e-6);
        if (right <= left) right = left + 1e-6;

        Words.boundsLeft[i] = left;
        Words.boundsRight[i] = right;
      }

      console.log(`[KARAOKE SYNC] ✓ ${Words.arr.length} palavras carregadas`);
    },

    findWordIndexAt: function(t) {
      var lo = 0, hi = Words.arr.length - 1, ans = -1;
      while (lo <= hi) {
        var mid = (lo + hi) >> 1;
        if (t < Words.boundsLeft[mid]) {
          hi = mid - 1;
        } else if (t >= Words.boundsRight[mid]) {
          lo = mid + 1;
        } else {
          ans = mid;
          break;
        }
      }
      return ans;
    },

    resetCustomTiming: function() {
      Highlight.isCustomTimingActive = false;
      if (Highlight.currentHighlightTimeout) {
        clearTimeout(Highlight.currentHighlightTimeout);
        Highlight.currentHighlightTimeout = null;
      }
      for (var i = 0; i < Words.arr.length; i++) {
        Words.arr[i].el.classList.remove('highlight-pause');
      }
    },

    at: function(t) {
      if (!Number.isFinite(t)) return;
      var dur = Utils.safeDuration();
      if (dur > 0) {
        if (t < 0) t = 0;
        if (t > dur + CONFIG.POST_ROLL + 1) t = dur + CONFIG.POST_ROLL + 1;
      }
      var idx = Highlight.findWordIndexAt(t);
      if (idx < 0) return;

      for (var i = 0; i < Words.arr.length; i++) {
        Words.arr[i].el.classList.remove('highlight');
      }

      var w = Words.arr[idx];
      w.el.classList.add('highlight');

      try {
        w.el.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'instant' });
      } catch (_) {
        var target = w.el.offsetTop - (DOM.transcriptWrap.clientHeight / 2);
        DOM.transcriptWrap.scrollTop = Math.max(0, target);
      }

      if (w.timeConfig && w.timeConfig.isTimeTag) {
        var isLastOfGroup = !(idx < Words.arr.length - 1 &&
          Words.arr[idx + 1].timeConfig &&
          Words.arr[idx + 1].timeConfig.group &&
          w.timeConfig.group &&
          Words.arr[idx + 1].timeConfig.group === w.timeConfig.group);

        if (isLastOfGroup) {
          var pauseDuration = (w.timeConfig.pause || 0) * 1000;
          if (pauseDuration > 0) {
            if (Highlight.currentHighlightTimeout) clearTimeout(Highlight.currentHighlightTimeout);
            Highlight.currentHighlightTimeout = setTimeout(function () {
              w.el.classList.add('highlight-pause');
              setTimeout(function () {
                w.el.classList.remove('highlight-pause');
              }, Math.min(250, pauseDuration));
            }, (w.timeConfig.duration || 0) * 1000);
          }
        }
      }
    }
  };

  // ==================== TICK (ANIMATION FRAME) ====================
  var Tick = {
    rafId: null,

    start: function() {
      if (!Tick.rafId) Tick.rafId = requestAnimationFrame(Tick.tick);
    },

    stop: function() {
      if (Tick.rafId) {
        cancelAnimationFrame(Tick.rafId);
        Tick.rafId = null;
      }
    },

    tick: function() {
      Highlight.at(DOM.audio.currentTime + CONFIG.SYNC_OFFSET);
      Player.updateUI();

      if (!DOM.audio.paused && !DOM.audio.ended) {
        Tick.rafId = requestAnimationFrame(Tick.tick);
      } else {
        Tick.rafId = null;
      }
    }
  };

  // ==================== INICIALIZAÇÃO ====================
  var j = DataLoader.load();
  if (!j) {
    console.error('[KARAOKE SYNC] Dados não carregados');
    return;
  }

  Transcript.build(j);
  Highlight.build();
  Player.init();

  if (DOM.playIcon && DOM.pauseIcon) {
    DOM.playIcon.style.display = 'inline-block';
    DOM.pauseIcon.style.display = 'none';
  }

  console.log('[KARAOKE SYNC] ✓ Pronto!');
});

// ==================== MENU TOGGLE ====================
document.addEventListener('DOMContentLoaded', function () {
  var menuToggle = document.getElementById("menuToggle");
  var navbarMenu = document.getElementById("navbarMenu");
  if (menuToggle && navbarMenu) {
    menuToggle.addEventListener("click", function () {
      navbarMenu.classList.toggle("show-menu");
    });
  }
	document.querySelectorAll('span').forEach(span => {
    // Normaliza o texto removendo espaços e quebras invisíveis
    const content = span.textContent.trim();

    if (content === ". -") {
        span.style.display = "none";
    }
});

});

</script>

<?php get_footer(); ?>
