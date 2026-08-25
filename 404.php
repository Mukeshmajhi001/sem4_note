<?php
// Optional: log the missing URL somewhere if you want
$requested = $_SERVER['REQUEST_URI'] ?? '/';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 — Lost the Path</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#12121a;
    --bg-soft:#181822;
    --ink:#f2efe9;
    --muted:#8c92a6;
    --accent:#e8a33d;
    --line:#2a2a38;
  }
  *{ box-sizing:border-box; margin:0; padding:0; }
  html,body{
    height:100%;
  }
  body{
    background:var(--bg);
    color:var(--ink);
    font-family:'Inter', system-ui, sans-serif;
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:100vh;
    padding:24px;
    overflow-x:hidden;
  }
  .scene{
    max-width:560px;
    width:100%;
    text-align:center;
  }
  .trail{
    width:100%;
    max-width:420px;
    height:120px;
    margin:0 auto 8px;
    display:block;
  }
  .trail path{
    fill:none;
    stroke:var(--muted);
    stroke-width:2;
    stroke-dasharray:6 10;
    stroke-linecap:round;
    stroke-dashoffset:400;
    animation:walk 1.8s ease-out forwards;
  }
  .trail circle{
    fill:var(--accent);
    opacity:0;
    animation:appear 0.4s ease-out 1.6s forwards;
  }
  @keyframes walk{
    to{ stroke-dashoffset:0; }
  }
  @keyframes appear{
    to{ opacity:1; }
  }
  .code{
    font-family:'Fraunces', serif;
    font-size:clamp(72px, 16vw, 128px);
    font-weight:600;
    line-height:0.9;
    letter-spacing:-0.02em;
    color:var(--ink);
    margin-bottom:4px;
  }
  .code span{
    color:var(--accent);
  }
  h1{
    font-family:'Fraunces', serif;
    font-weight:500;
    font-size:22px;
    margin:12px 0 10px;
    color:var(--ink);
  }
  p{
    color:var(--muted);
    font-size:15px;
    line-height:1.6;
    max-width:400px;
    margin:0 auto 32px;
  }
  p code{
    background:var(--bg-soft);
    border:1px solid var(--line);
    padding:2px 7px;
    border-radius:5px;
    font-size:13px;
    color:var(--ink);
    font-family:'Inter', monospace;
  }
  .actions{
    display:flex;
    gap:12px;
    justify-content:center;
    flex-wrap:wrap;
  }
  .btn{
    font-family:'Inter', sans-serif;
    font-size:14px;
    font-weight:600;
    text-decoration:none;
    padding:12px 22px;
    border-radius:8px;
    transition:transform 0.15s ease, background 0.15s ease, border-color 0.15s ease;
    display:inline-block;
  }
  .btn:focus-visible{
    outline:2px solid var(--accent);
    outline-offset:3px;
  }
  .btn-primary{
    background:var(--accent);
    color:#1a1408;
  }
  .btn-primary:hover{
    transform:translateY(-1px);
    background:#f0ae4d;
  }
  .btn-ghost{
    background:transparent;
    color:var(--ink);
    border:1px solid var(--line);
  }
  .btn-ghost:hover{
    border-color:var(--muted);
    transform:translateY(-1px);
  }
  @media (prefers-reduced-motion: reduce){
    .trail path, .trail circle{
      animation:none;
      stroke-dashoffset:0;
      opacity:1;
    }
  }
</style>
</head>
<body>
  <main class="scene">
    <svg class="trail" viewBox="0 0 420 120" aria-hidden="true">
      <path d="M 10 100 Q 90 20, 170 70 T 330 40" />
      <circle cx="330" cy="40" r="5" />
    </svg>

    <div class="code">4<span>0</span>4</div>
    <h1>This path leads nowhere</h1>
    <p>
      The page you're looking for isn't here.
      The link might be broken, or the page may have moved — <code><?php echo htmlspecialchars($requested); ?></code>
    </p>

    <div class="actions">
      <a href="/" class="btn btn-primary">Go home</a>
      <a href="javascript:history.back()" class="btn btn-ghost">Go back</a>
    </div>
  </main>
</body>
</html>