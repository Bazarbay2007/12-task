document.addEventListener('DOMContentLoaded', function () {
    // Мобильді мәзір
    var burger = document.getElementById('burger');
    var menu = document.getElementById('menu');
    if (burger && menu) {
        burger.addEventListener('click', function () { menu.classList.toggle('open'); });
    }

    // Жоюды растау
    document.querySelectorAll('.delete-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!confirm('Бұл тапсырманы жоюға сенімдісіз бе?')) { e.preventDefault(); }
        });
    });

    // Жалпы растау (data-confirm)
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!confirm(f.dataset.confirm)) { e.preventDefault(); }
        });
    });

    // Құпиясөзді көрсету/жасыру
    document.querySelectorAll('.pw-toggle').forEach(function (b) {
        b.addEventListener('click', function () {
            var i = document.getElementById(b.dataset.target);
            i.type = i.type === 'password' ? 'text' : 'password';
        });
    });

    // Символ санағышы
    document.querySelectorAll('textarea[data-counter]').forEach(function (t) {
        var out = document.getElementById(t.dataset.counter);
        var upd = function () { out.textContent = t.value.length; };
        t.addEventListener('input', upd); upd();
    });

    // Хабарламаны автоматты жасыру
    document.querySelectorAll('[data-autohide]').forEach(function (a) {
        setTimeout(function () { a.style.opacity = '0'; setTimeout(function () { a.remove(); }, 400); }, 4000);
    });

    // 🔊 Айтылым (браузердің SpeechSynthesis)
    function say(text) {
        if (!('speechSynthesis' in window)) { return; }
        var u = new SpeechSynthesisUtterance(text);
        u.lang = 'en-US'; u.rate = 0.9;
        window.speechSynthesis.cancel(); window.speechSynthesis.speak(u);
    }
    document.querySelectorAll('.speak[data-word]').forEach(function (b) {
        b.addEventListener('click', function () { say(b.dataset.word); });
    });

    // Аватар алдын ала көру
    var ai = document.getElementById('avatar-input');
    if (ai) {
        ai.addEventListener('change', function () {
            var f = ai.files[0];
            if (!f) { return; }
            var box = document.getElementById('avatar-preview');
            box.innerHTML = '';
            var img = document.createElement('img');
            img.className = 'avatar avatar-xl';
            img.src = URL.createObjectURL(f);
            box.appendChild(img);
        });
    }

    // Сөз жаттау карточкалары
    var fl = document.getElementById('flash');
    if (fl) {
        var cards = JSON.parse(document.getElementById('flash-data').textContent);
        var i = 0, shown = false;
        var $ = function (id) { return document.getElementById(id); };
        function render() {
            if (i >= cards.length) {
                $('fc-card').hidden = true; document.querySelector('.fc-actions').hidden = true;
                $('fc-done').hidden = false; $('fc-count').textContent = '';
                return;
            }
            var c = cards[i]; shown = false;
            $('fc-word').textContent = c.word;
            $('fc-trans').textContent = '';
            $('fc-ex').textContent = '';
            $('fc-count').textContent = (i + 1) + ' / ' + cards.length;
            $('fc-flip').textContent = 'Аудармасын көрсету';
        }
        function flip() {
            var c = cards[i]; if (!c) { return; }
            shown = !shown;
            $('fc-trans').textContent = shown ? c.translation : '';
            $('fc-ex').textContent = shown && c.example ? c.example : '';
            $('fc-flip').textContent = shown ? 'Жасыру' : 'Аудармасын көрсету';
        }
        $('fc-flip').addEventListener('click', flip);
        $('fc-card').addEventListener('click', function (e) { if (!e.target.closest('.speak')) { flip(); } });
        $('fc-speak').addEventListener('click', function () { if (cards[i]) { say(cards[i].word); } });
        $('fc-again').addEventListener('click', function () { i++; render(); });
        $('fc-know').addEventListener('click', function () {
            var fd = new FormData();
            fd.append('action', 'toggle'); fd.append('id', cards[i].id); fd.append('csrf', fl.dataset.csrf);
            fetch('words.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' } });
            i++; render();
        });
        render();
    }
});
