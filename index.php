<?php
// Laeb .env faili
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
$_ENV[trim($key)] = trim($value);
    }
}

define('DB_HOST', $_ENV['DB_HOST'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// ── API päringud ────────────────────────────────────────────
if (isset($_GET['action'])) {
    session_start();
    if (empty($_SESSION['vote_id'])) {
        $_SESSION['vote_id'] = bin2hex(random_bytes(32));
    }
    $sid = $_SESSION['vote_id'];

    header('Content-Type: application/json');

    try {
        $db = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Loo tabel kui pole olemas
        $db->exec("CREATE TABLE IF NOT EXISTS paev_votes (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            vote       ENUM('up','down') NOT NULL,
            feedback   TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_session (session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if ($_GET['action'] === 'get') {
            $s = $db->prepare('SELECT vote FROM paev_votes WHERE session_id = ?');
            $s->execute([$sid]);
            $row = $s->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'vote' => $row ? $row['vote'] : null]);

        } elseif ($_GET['action'] === 'vote') {
            $input    = json_decode(file_get_contents('php://input'), true);
            $vote     = $input['vote']     ?? null;
            $feedback = $input['feedback'] ?? null;
            if (!in_array($vote, ['up','down'])) { echo json_encode(['success'=>false]); exit; }
            $feedback = $feedback ? substr(trim($feedback), 0, 1000) : null;
            $s = $db->prepare('INSERT INTO paev_votes (session_id, vote, feedback) VALUES (?,?,?)
                               ON DUPLICATE KEY UPDATE vote=VALUES(vote), feedback=VALUES(feedback)');
            $s->execute([$sid, $vote, $feedback]);
            echo json_encode(['success' => true, 'vote' => $vote]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="et">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Päeva Hääletus</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #0f0f0f; --surface: #1a1a1a; --border: #2a2a2a;
      --text: #e8e4dc; --muted: #555;
      --up: #22c55e; --down: #ef4444; --accent: #f0e96a;
    }
    body {
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text);
    }
    body::before {
      content: ''; position: fixed; inset: 0; pointer-events: none;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
      opacity: .035;
    }
    .card {
      position: relative; z-index: 1; background: var(--surface);
      border: 1px solid var(--border); border-radius: 28px;
      padding: 56px 64px 52px; max-width: 480px; width: 90vw; text-align: center;
      box-shadow: 0 0 0 1px #ffffff08, 0 32px 80px #00000060;
      animation: fadeUp .5s ease both;
    }
    @keyframes fadeUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
    .label { font-size: 11px; font-weight: 300; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); margin-bottom: 18px; }
    h1 { font-family: 'Syne', sans-serif; font-size: clamp(1.5rem,5vw,2rem); font-weight: 800; line-height: 1.15; margin-bottom: 44px; }
    h1 span { color: var(--accent); }
    .votes { display: flex; gap: 20px; justify-content: center; margin-bottom: 32px; }
    .vote-btn {
      display: flex; flex-direction: column; align-items: center; gap: 10px;
      background: none; border: 2px solid var(--border); border-radius: 20px;
      padding: 26px 36px 22px; cursor: pointer; transition: border-color .25s, background .25s, transform .15s;
      outline: none; position: relative; overflow: hidden;
    }
    .vote-btn:hover { transform: translateY(-3px); }
    .vote-btn:active { transform: scale(.96); }
    .thumb-icon { font-size: 2.6rem; line-height: 1; transition: transform .3s cubic-bezier(.34,1.56,.64,1); display: block; }
    .btn-label { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); font-weight: 300; transition: color .25s; }
    .vote-btn.active-up   { border-color: var(--up);   background: #22c55e12; }
    .vote-btn.active-up   .btn-label { color: var(--up); }
    .vote-btn.active-up   .thumb-icon { transform: scale(1.25) rotate(-8deg); }
    .vote-btn.active-down { border-color: var(--down); background: #ef444412; }
    .vote-btn.active-down .btn-label { color: var(--down); }
    .vote-btn.active-down .thumb-icon { transform: scale(1.25) rotate(8deg); }
    .vote-btn.inactive { border-color: #3f3f3f; opacity: .45; }
    #status-msg { font-size: 13px; color: var(--muted); min-height: 20px; font-weight: 300; transition: color .3s; }
    #status-msg.ok  { color: var(--up); }
    #status-msg.bad { color: var(--down); }
    .divider { width: 40px; height: 1px; background: var(--border); margin: 28px auto 0; }

    /* Modal */
    .overlay {
      position: fixed; inset: 0; background: #00000088; backdrop-filter: blur(6px);
      display: flex; align-items: center; justify-content: center; z-index: 100;
      opacity: 0; pointer-events: none; transition: opacity .3s;
    }
    .overlay.open { opacity: 1; pointer-events: all; }
    .modal {
      background: var(--surface); border: 1px solid var(--border); border-radius: 22px;
      padding: 40px 36px 36px; max-width: 420px; width: 88vw;
      box-shadow: 0 24px 80px #00000080;
      transform: scale(.92); transition: transform .35s cubic-bezier(.34,1.56,.64,1);
    }
    .overlay.open .modal { transform: scale(1); }
    .modal h2 { font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 800; margin-bottom: 8px; }
    .modal p  { font-size: 13px; color: var(--muted); font-weight: 300; margin-bottom: 22px; }
    textarea {
      width: 100%; min-height: 110px; background: var(--bg); border: 1px solid var(--border);
      border-radius: 12px; color: var(--text); font-family: 'DM Sans', sans-serif;
      font-size: 14px; padding: 14px 16px; resize: vertical; outline: none;
      transition: border-color .2s; margin-bottom: 18px;
    }
    textarea:focus { border-color: var(--down); }
    textarea::placeholder { color: var(--muted); }
    .modal-btns { display: flex; gap: 12px; }
    .btn-send {
      flex: 2; padding: 13px; border: none; border-radius: 12px; background: var(--down);
      color: #fff; font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer;
      transition: opacity .2s;
    }
    .btn-send:hover { opacity: .85; }

    @keyframes ripple { from { transform:scale(0); opacity:.35; } to { transform:scale(4); opacity:0; } }
    .ripple {
      position: absolute; border-radius: 50%; width: 60px; height: 60px;
      pointer-events: none; animation: ripple .55s ease-out forwards;
      margin-top: -30px; margin-left: -30px;
    }
  </style>
</head>
<body>

<div class="card">
  <p class="label">Päevane küsimus</p>
  <h1>Kas täna on<br /><span>ok päev?</span></h1>
  <div class="votes">
    <button class="vote-btn" id="btn-up" onclick="handleVote('up',event)">
      <span class="thumb-icon">👍</span>
      <span class="btn-label">Jah</span>
    </button>
    <button class="vote-btn" id="btn-down" onclick="handleVote('down',event)">
      <span class="thumb-icon">👎</span>
      <span class="btn-label">Ei</span>
    </button>
  </div>
  <p id="status-msg">Vali oma vastus</p>
  <div class="divider"></div>
</div>

<div class="overlay" id="overlay" onclick="overlayClick(event)">
  <div class="modal">
    <h2>Mis läks valesti? 🤔</h2>
    <p>Sinu tagasiside aitab meid. Pole kohustuslik.</p>
    <textarea id="feedback-text" placeholder="Kirjuta siia…" maxlength="1000"></textarea>
    <div class="modal-btns">
      <button class="btn-send" onclick="submitFeedback(true)">Saada tagasiside</button>
    </div>
  </div>
</div>

<script>
  const API = 'index.php';
  let currentVote = null;

  (async () => {
    try {
      const r = await fetch(`${API}?action=get`);
      const d = await r.json();
      if (d.success && d.vote) { currentVote = d.vote; applyState(d.vote); }
    } catch(e) {}
  })();

  async function handleVote(vote, event) {
    addRipple(event.currentTarget, vote === 'up' ? '#22c55e' : '#ef4444');
    if (vote === 'down') { openModal(); return; }
    await saveVote('up', null);
    currentVote = 'up';
    applyState('up');
  }

  function openModal() {
    document.getElementById('feedback-text').value = '';
    document.getElementById('overlay').classList.add('open');
    setTimeout(() => document.getElementById('feedback-text').focus(), 350);
  }
  function closeModal() { document.getElementById('overlay').classList.remove('open'); }
  function overlayClick(e) { if (e.target === document.getElementById('overlay')) closeModal(); }

  async function submitFeedback(withText) {
    const text = withText ? document.getElementById('feedback-text').value.trim() : null;
    closeModal();
    await saveVote('down', text || null);
    currentVote = 'down';
    applyState('down');
  }

  async function saveVote(vote, feedback) {
    try {
      await fetch(`${API}?action=vote`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ vote, feedback })
      });
    } catch(e) {}
  }

  function applyState(vote) {
    const up = document.getElementById('btn-up');
    const dn = document.getElementById('btn-down');
    const msg = document.getElementById('status-msg');
    up.className = 'vote-btn'; dn.className = 'vote-btn';
    if (vote === 'up') {
      up.classList.add('active-up'); dn.classList.add('inactive');
      msg.textContent = 'Suurepärane! Tore päev! ✨'; msg.className = 'ok';
    } else {
      dn.classList.add('active-down'); up.classList.add('inactive');
      msg.textContent = 'Aitäh, et jagasid. Hoiame pead püsti! 💙'; msg.className = 'bad';
    }
  }

  function addRipple(btn, color) {
    const r = document.createElement('span');
    r.className = 'ripple'; r.style.background = color;
    r.style.top = '50%'; r.style.left = '50%';
    btn.appendChild(r);
    r.addEventListener('animationend', () => r.remove());
  }

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
</body>
</html>