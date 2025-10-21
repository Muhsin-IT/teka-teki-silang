<?php
$questionsJson = @file_get_contents(__DIR__ . '/questions.json');
$questions = $questionsJson ? json_decode($questionsJson, true) : [];
if (!is_array($questions)) $questions = [];
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>TTS Layar Proyektor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body class="proj container">
  <div class="topbar">
    <div class="title m-3">Lomba TTS - Layar</div>
    <div class="teams" id="teamsDisplay"></div>
  </div>

  <main>
    <div class="crossword-wrapper">
      <div class="crossword-grid" id="crosswordGrid">
        <!-- JS akan membangun sel di sini -->
      </div>



      <div class="panel">
        <div id="questionArea" class="questionArea">Pilih nomor soal di admin.</div>
        <div class="status" id="statusArea"></div>
        <div class="controls small">Last update: <span id="lastUpdate">-</span></div>
      </div>
  </main>

  <script src="assets/scrip.js"></script>
  <script>
    // init projector with questions data
    const QUESTIONS = <?= json_encode($questions) ?>;
    initProjector(QUESTIONS);
  </script>
</body>

</html>