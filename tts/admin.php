<?php
$questionsJson = @file_get_contents(__DIR__ . '/questions.json');
$questions = $questionsJson ? json_decode($questionsJson, true) : [];
if (!is_array($questions)) $questions = [];
// include __DIR__ . '/navbar.php';
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Kontrol TTS</title>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body class="admin">
  <div class="admin-wrapper">
    <header>
      <h1>Admin Panel - TTS</h1>
      <div class="teams-control">
        <label>Tim A: <input type="number" id="scoreA" value="0"></label>
        <label>Tim B: <input type="number" id="scoreB" value="0"></label>
        <label>Tim C: <input type="number" id="scoreC" value="0"></label>
        <label>Tim D: <input type="number" id="scoreD" value="0"></label>
      </div>
    </header>

    <section class="admin-grid">
      <?php foreach ($questions as $q): ?>
        <button class="admin-cell" data-no="<?= $q['no'] ?>"><?= $q['no'] ?>. <?= htmlspecialchars($q['question']) ?></button>
      <?php endforeach; ?>
    </section>

    <section class="admin-actions">
      <button id="revealBtn">Reveal Jawaban</button>
      <button id="markCorrect">Tandai Benar (+10)</button>
      <button id="markWrong">Tandai Salah</button>
      <button id="resetBtn">Reset Semua</button>
    </section>
  </div>

  <!-- load correct script filename -->
  <script src="assets/scrip.js"></script>
  <script>
    const QUESTIONS = <?= json_encode($questions) ?>;
    initAdmin(QUESTIONS);
  </script>
</body>

</html>