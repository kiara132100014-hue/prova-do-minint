(function () {
  'use strict';
  var cfg = window.QUIZ;
  var $ = function (s) { return document.querySelector(s); };
  var S = { qs: [], i: 0, mode: 'treino', left: null, timer: null, busy: false, done: false };

  function api(action, data) {
    var opt = {}, url;
    if (data) {
      url = cfg.api;
      opt = { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrf },
              body: JSON.stringify(Object.assign({ action: action, id: cfg.id }, data)) };
    } else {
      url = cfg.api + '?action=' + action + '&id=' + cfg.id;
    }
    return fetch(url, opt).then(function (r) { return r.json(); });
  }

  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function fmt(s) { var m = Math.floor(s / 60), x = s % 60; return (m < 10 ? '0' : '') + m + ':' + (x < 10 ? '0' : '') + x; }

  function render() {
    var q = S.qs[S.i];
    if (!q) return;
    var answered = S.qs.filter(function (x) { return x.chosen; }).length;
    $('#counter').textContent = 'Pergunta ' + (S.i + 1) + ' de ' + S.qs.length + ' · respondidas ' + answered;
    $('#prog').style.width = (answered * 100 / S.qs.length) + '%';
    $('#qmeta').textContent = q.cat;
    $('#qtext').textContent = q.text;

    var locked = S.mode === 'treino' && q.chosen;
    var html = '';
    ['A', 'B', 'C', 'D'].forEach(function (L) {
      var cls = 'opt';
      if (q.chosen === L) cls += ' sel';
      if (locked) {
        if (L === q.correct) cls += ' ok';
        else if (L === q.chosen) cls += ' no';
      }
      html += '<button class="' + cls + '" data-l="' + L + '"' + (locked ? ' disabled' : '') + '><b>' + L + '</b><span>' + esc(q.options[L]) + '</span></button>';
    });
    $('#opts').innerHTML = html;

    var fb = '';
    if (locked) {
      var good = q.chosen === q.correct;
      fb = '<div class="fb ' + (good ? 'good' : 'bad') + '"><b>' + (good ? '✔ Resposta correcta' : '✘ Resposta errada — a correcta é a ' + q.correct) + '</b><p>' + esc(q.explanation || '') + '</p></div>';
    }
    $('#feedback').innerHTML = fb;

    $('#prev').disabled = S.i === 0;
    $('#next').disabled = S.i === S.qs.length - 1;

    var g = '';
    S.qs.forEach(function (x, k) {
      var c = 'qb';
      if (k === S.i) c += ' cur';
      if (x.chosen) {
        c += ' done';
        if (S.mode === 'treino') c += (x.chosen === x.correct ? ' ok' : ' no');
      }
      g += '<button class="' + c + '" data-i="' + k + '">' + (k + 1) + '</button>';
    });
    $('#grid').innerHTML = g;
  }

  function choose(L) {
    var q = S.qs[S.i];
    if (!q || S.busy) return;
    if (S.mode === 'treino' && q.chosen) return;
    S.busy = true;
    var prev = q.chosen;
    if (S.mode === 'exame') { q.chosen = L; render(); }
    api('answer', { qid: q.qid, choice: L }).then(function (r) {
      S.busy = false;
      if (r.finished) { location.href = cfg.result; return; }
      if (r.error) { if (S.mode === 'exame') { q.chosen = prev; render(); } alert(r.error); return; }
      if (S.mode === 'treino') { q.chosen = r.chosen; q.correct = r.correct; q.explanation = r.explanation; }
      render();
    }).catch(function () {
      S.busy = false;
      if (S.mode === 'exame') { q.chosen = prev; render(); }
      alert('Falha de ligação. Tente novamente.');
    });
  }

  function go(n) { if (n >= 0 && n < S.qs.length) { S.i = n; render(); window.scrollTo(0, 0); } }

  function finish(auto) {
    if (S.done) return;
    var un = S.qs.filter(function (x) { return !x.chosen; }).length;
    if (!auto) {
      var m = un > 0 ? 'Ainda tem ' + un + ' pergunta(s) sem resposta. Terminar mesmo assim?' : 'Terminar e ver o resultado?';
      if (!confirm(m)) return;
    }
    S.done = true;
    clearInterval(S.timer);
    api('finish', {}).then(function (r) { location.href = (r && r.url) || cfg.result; })
      .catch(function () { S.done = false; alert('Falha de ligação. Tente terminar novamente.'); });
  }

  function tick() {
    var t = $('#timer');
    t.textContent = fmt(Math.max(0, S.left));
    t.classList.toggle('low', S.left <= 60);
    if (S.left <= 0) { clearInterval(S.timer); finish(true); return; }
    S.left--;
  }

  document.addEventListener('click', function (ev) {
    var o = ev.target.closest('.opt');
    if (o && !o.disabled) return choose(o.getAttribute('data-l'));
    var b = ev.target.closest('.qb');
    if (b) return go(parseInt(b.getAttribute('data-i'), 10));
  });
  $('#prev').addEventListener('click', function () { go(S.i - 1); });
  $('#next').addEventListener('click', function () { go(S.i + 1); });
  $('#finish').addEventListener('click', function () { finish(false); });
  document.addEventListener('keydown', function (ev) {
    if (ev.ctrlKey || ev.metaKey || ev.altKey) return;
    var k = ev.key.toUpperCase();
    if (['A', 'B', 'C', 'D'].indexOf(k) >= 0) choose(k);
    else if (ev.key === 'ArrowRight') go(S.i + 1);
    else if (ev.key === 'ArrowLeft') go(S.i - 1);
  });

  api('load').then(function (r) {
    if (r.finished) { location.href = cfg.result; return; }
    if (r.error) { $('#qtext').textContent = r.error; return; }
    S.qs = r.questions; S.mode = r.mode; S.left = r.left;
    var first = S.qs.findIndex(function (x) { return !x.chosen; });
    S.i = first >= 0 ? first : 0;
    render();
    if (S.mode === 'exame') { tick(); S.timer = setInterval(tick, 1000); }
  }).catch(function () { $('#qtext').textContent = 'Não foi possível carregar a simulação.'; });
})();
