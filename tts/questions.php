<?php
// Data soal dengan posisi dan arah untuk grid crossword
$storageFile = __DIR__ . '/questions.json';

// default questions (dipakai saat belum ada questions.json)
$defaultQuestions = [
    // no, question, answer (no spaces), x (col), y (row), dir ('across'|'down')
    ['no' => 1, 'question' => 'Ibu kota Indonesia adalah?', 'answer' => 'JAKARTA', 'x' => 10, 'y' => 1, 'dir' => 'down'],
    ['no' => 2, 'question' => 'Planet paling dekat dengan Matahari?', 'answer' => 'MERKURIUS', 'x' => 2, 'y' => 2, 'dir' => 'across'],
    ['no' => 3, 'question' => 'Warna bendera Indonesia (dua kata)?', 'answer' => 'MERAHPUTIH', 'x' => 4, 'y' => 1, 'dir' => 'down'],
    ['no' => 4, 'question' => 'Alat untuk melihat bintang?', 'answer' => 'TELESKOP', 'x' => 1, 'y' => 4, 'dir' => 'across'],
    ['no' => 5, 'question' => 'Bahasa pemrograman backend web?', 'answer' => 'PHP', 'x' => 9, 'y' => 5, 'dir' => 'across'],
    ['no' => 6, 'question' => 'Ibukota Provinsi Jawa Tengah?', 'answer' => 'SEMARANG', 'x' => 12, 'y' => 3, 'dir' => 'down'],
    ['no' => 7, 'question' => 'Satuan untuk arus listrik?', 'answer' => 'AMPERE', 'x' => 3, 'y' => 7, 'dir' => 'across'],
    ['no' => 8, 'question' => 'Simbol kimia air?', 'answer' => 'H2O', 'x' => 1, 'y' => 9, 'dir' => 'down'],
    ['no' => 9, 'question' => 'Hari kemerdekaan Indonesia? (tanggal dan bulan)', 'answer' => '17AGUSTUS', 'x' => 4, 'y' => 6, 'dir' => 'across'],
    ['no' => 10, 'question' => 'Nama ilmuwan penemu relativitas?', 'answer' => 'EINSTEIN', 'x' => 6, 'y' => 1, 'dir' => 'down']
];

// load from storage if exists
if (file_exists($storageFile)) {
    $raw = file_get_contents($storageFile);
    $questions = json_decode($raw, true);
    if (!is_array($questions)) $questions = $defaultQuestions;
} else {
    $questions = $defaultQuestions;
    // buat file storage awal agar persistensi siap (jika direktori writable)
    @file_put_contents($storageFile, json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// pastikan $errors selalu terdefinisi sebelum tampilan
$errors = [];

// Jika file ini diakses langsung (bukan di-include), tampilkan halaman input sederhana
if (count(debug_backtrace()) === 0) {
    // handle POST add / delete / save_positions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // simpan posisi dari preview (drag & drop)
        if (isset($_POST['action']) && $_POST['action'] === 'save_positions') {
            $positionsJson = $_POST['positions'] ?? '';
            $positions = json_decode($positionsJson, true);
            if (is_array($positions)) {
                foreach ($positions as $p) {
                    $no = isset($p['no']) ? (int)$p['no'] : null;
                    if ($no === null) continue;
                    foreach ($questions as &$q) {
                        if ((int)$q['no'] === $no) {
                            if (isset($p['x'])) $q['x'] = (int)$p['x'];
                            if (isset($p['y'])) $q['y'] = (int)$p['y'];
                            if (isset($p['dir']) && in_array($p['dir'], ['across', 'down'])) $q['dir'] = $p['dir'];
                            break;
                        }
                    }
                    unset($q);
                }
                @file_put_contents($storageFile, json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // jika aksi delete dikirimkan
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $delNo = filter_input(INPUT_POST, 'no', FILTER_VALIDATE_INT);
            if ($delNo !== false && $delNo !== null) {
                $found = false;
                foreach ($questions as $idx => $q) {
                    if ((int)$q['no'] === (int)$delNo) {
                        $found = true;
                        array_splice($questions, $idx, 1);
                        break;
                    }
                }
                if ($found) {
                    @file_put_contents($storageFile, json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    $errors[] = 'Soal tidak ditemukan untuk dihapus.';
                }
            } else {
                $errors[] = 'Nomor soal tidak valid untuk penghapusan.';
            }
            // redirect untuk mencegah resubmit
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // sederhana: ambil input, sanitize
        $no = filter_input(INPUT_POST, 'no', FILTER_VALIDATE_INT);
        $questionText = trim(filter_input(INPUT_POST, 'question', FILTER_UNSAFE_RAW));
        $answerRaw = trim(filter_input(INPUT_POST, 'answer', FILTER_UNSAFE_RAW));
        // normalisasi: hapus spasi dan uppercase
        $answer = strtoupper(str_replace(' ', '', $answerRaw));
        $x = filter_input(INPUT_POST, 'x', FILTER_VALIDATE_INT);
        $y = filter_input(INPUT_POST, 'y', FILTER_VALIDATE_INT);
        $dir = in_array($_POST['dir'] ?? '', ['across', 'down']) ? $_POST['dir'] : 'across';

        // validasi minimal
        $errors = [];
        if ($no === false || $no <= 0) $errors[] = 'No harus angka positif.';
        if ($questionText === '') $errors[] = 'Field pertanyaan kosong.';
        if ($answer === '') $errors[] = 'Field jawaban kosong.';
        // pastikan jawaban hanya 1 kata: huruf dan/atau angka saja (no punctuation, no separators)
        if ($answer !== '' && !preg_match('/^[A-Z0-9]+$/', $answer)) {
            $errors[] = 'Jawaban harus satu kata (huruf/angka) tanpa spasi atau karakter khusus.';
        }
        if ($x === false || $x < 0) $errors[] = 'Kolom (x) harus angka 0+.';
        if ($y === false || $y < 0) $errors[] = 'Baris (y) harus angka 0+.';

        if (empty($errors)) {
            // pastikan tidak duplikat nomor
            $exists = false;
            foreach ($questions as $q) {
                if ((int)$q['no'] === (int)$no) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) {
                $errors[] = 'Nomor soal sudah ada.';
            } else {
                $new = [
                    'no' => (int)$no,
                    'question' => $questionText,
                    'answer' => $answer,
                    'x' => (int)$x,
                    'y' => (int)$y,
                    'dir' => $dir
                ];
                $questions[] = $new;
                // simpan ke JSON
                @file_put_contents($storageFile, json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                // redirect untuk mencegah resubmit
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }

    // tampilkan form & daftar soal (sederhana)
?>
    <!doctype html>
    <html>

    <head>
        <meta charset="utf-8">
        <title>Admin Input Soal - TTS</title>
        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                margin: 20px
            }

            form {
                max-width: 700px;
                background: #f9f9f9;
                padding: 12px;
                border-radius: 6px
            }

            label {
                display: block;
                margin: 8px 0
            }

            input[type=text],
            input[type=number] {
                width: 100%;
                padding: 6px
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 12px
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left
            }

            .errors {
                color: #b00
            }

            .delbtn {
                background: #e74c3c;
                color: #fff;
                border: none;
                padding: 4px 8px;
                border-radius: 3px;
                cursor: pointer
            }

            /* grid preview styles */
            .grid-wrap {
                margin-top: 20px;
                display: flex;
                gap: 20px;
                align-items: flex-start;
            }

            .grid {
                display: grid;
                grid-template-columns: repeat(15, 28px);
                gap: 2px;
                background: #ddd;
                padding: 6px;
                border-radius: 6px;
            }

            .cell {
                width: 28px;
                height: 28px;
                background: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                cursor: pointer;
                border: 1px solid #eee;
            }

            .cell.drop-hover {
                background: #f0f8ff;
            }

            .cell.placed {
                background: #e3f7e3;
                font-weight: 600;
                color: #111;
            }

            .cell.conflict {
                background: #ffd6d6;
                color: #900;
                font-weight: 700;
            }

            /* nomor kecil untuk menandai start cell */
            .startnos {
                display: block;
                position: absolute;
                top: 1px;
                left: 2px;
                font-size: 10px;
                color: #333;
                opacity: 0.95;
            }

            .cell {
                position: relative;
            }

            .letters {
                display: flex;
                gap: 2px;
                align-items: center;
                justify-content: center;
            }

            .letter-main {
                font-weight: 700;
                font-size: 12px;
                line-height: 1;
            }

            .letter-sub {
                font-size: 9px;
                opacity: 0.9;
            }
        </style>
        <script>
            function confirmDelete(form) {
                if (confirm('Hapus soal ini?')) {
                    form.submit();
                }
                return false;
            }
        </script>
    </head>

    <body>

        <?php include __DIR__ . '/navbar.php'; ?>
        <h1>Input Soal (Admin)</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors"><strong>Error:</strong><br><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
        <?php endif; ?>

        <form method="post">
            <label>No soal <input type="number" name="no" required></label>
            <label>Pertanyaan <input type="text" name="question" required></label>
            <label>Jawaban (satu kata — huruf/angka, tanpa spasi/karakter khusus) <input type="text" name="answer" required></label>
            <label>Kolom x <input type="number" name="x" value="1" required></label>
            <label>Baris y <input type="number" name="y" value="1" required></label>
            <label>Arah
                <select name="dir">
                    <option value="across">across</option>
                    <option value="down">down</option>
                </select>
            </label>
            <p><button type="submit">Tambah Soal</button></p>
        </form>

        <!-- New: Drag & Drop preview -->
        <h2>Preview Grid (Drag & Drop untuk menempatkan jawaban)</h2>
        <div class="grid-wrap">
            <div>
                <div class="controls">
                    <label>Ukuran grid:
                        <select id="gridSize">
                            <option value="15">15x15</option>
                            <option value="12">12x12</option>
                            <option value="10">10x10</option>
                        </select>
                    </label>
                    <button id="resetGrid" type="button">Reset Preview</button>
                </div>
                <div id="grid" class="grid" aria-label="Grid preview"></div>
                <p><small>Drop soal ke sel awal (x,y) — arah disesuaikan dengan pilihan dir per soal.</small></p>
            </div>

            <div>
                <h3>Daftar Soal (seret dari sini)</h3>
                <ul id="draggableList" class="draggable-list"></ul>
                <p><button id="savePosBtn" class="savebtn" type="button">Simpan Posisi</button></p>

                <form id="posForm" method="post" style="display:none;">
                    <input type="hidden" name="action" value="save_positions">
                    <input id="positionsInput" type="hidden" name="positions" value="">
                </form>
            </div>
        </div>

        <h2>Daftar Soal (tersimpan)</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Pertanyaan</th>
                    <th>Jawaban</th>
                    <th>x</th>
                    <th>y</th>
                    <th>dir</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($q['no']); ?></td>
                        <td><?php echo htmlspecialchars($q['question']); ?></td>
                        <td><?php echo htmlspecialchars($q['answer']); ?></td>
                        <td><?php echo htmlspecialchars($q['x']); ?></td>
                        <td><?php echo htmlspecialchars($q['y']); ?></td>
                        <td><?php echo htmlspecialchars($q['dir']); ?></td>
                        <td>
                            <form method="post" style="display:inline" onsubmit="return confirmDelete(this);">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="no" value="<?php echo (int)$q['no']; ?>">
                                <button type="submit" class="delbtn">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
            // build JS model from PHP
            const questions = <?php echo json_encode(array_values($questions)); ?>;
            let gridSize = parseInt(document.getElementById('gridSize').value, 10) || 15;
            const gridEl = document.getElementById('grid');
            const listEl = document.getElementById('draggableList');
            const placed = {}; // no => {no,x,y,dir}
            const cellMap = {}; // key -> { no: letter, ... }

            function setCellVisual(key) {
                const cell = document.querySelector(`.cell[data-key="${key}"]`);
                if (!cell) return;
                const map = cellMap[key];
                // if no letters, clear visual but keep start-nos handling
                if (!map || Object.keys(map).length === 0) {
                    cell.classList.remove('conflict', 'placed');
                    delete cell.dataset.placedNos;
                    cell.innerHTML = cell.dataset.startNos ? `<span class="startnos">${cell.dataset.startNos}</span>` : '';
                    if (!cell.dataset.startNos) {
                        cell.draggable = false;
                        cell.ondragstart = null;
                        cell.title = '';
                    }
                    return;
                }

                // Build deterministic ordering: sort entries by numeric question no
                const entries = Object.entries(map).sort((a, b) => Number(a[0]) - Number(b[0])); // [ [no,letter], ... ]
                // letters in order of question no
                const letters = entries.map(e => e[1]);
                // dedupe while preserving order (so same letter from multiple words won't duplicate)
                const uniqueLetters = [];
                for (const ch of letters)
                    if (!uniqueLetters.includes(ch)) uniqueLetters.push(ch);
                const allSame = uniqueLetters.length === 1;
                const startNos = cell.dataset.startNos ? cell.dataset.startNos : '';
                let html = startNos ? `<span class="startnos">${startNos}</span>` : '';
                html += `<span class="letters">`;
                if (allSame) {
                    html += `<span class="letter-main">${uniqueLetters[0]}</span>`;
                } else {
                    html += `<span class="letter-main">${uniqueLetters[0]}</span>`;
                    for (let i = 1; i < uniqueLetters.length; i++) {
                        html += `<span class="letter-sub">${uniqueLetters[i]}</span>`;
                    }
                }
                html += `</span>`;
                cell.innerHTML = html;
                cell.classList.toggle('conflict', !allSame);
                cell.classList.add('placed');
                cell.dataset.placedNos = Object.keys(map).join(',');
            }

            function clearQuestionFromCells(no) {
                // remove letters belonging to question no
                for (const key in cellMap) {
                    if (cellMap[key] && cellMap[key][no] !== undefined) {
                        delete cellMap[key][no];
                        setCellVisual(key);
                    }
                }
                // Remove this no from any cells that have start-nos (iterate all and filter)
                const startCells = document.querySelectorAll('.cell[data-start-nos]');
                startCells.forEach(el => {
                    const arr = el.dataset.startNos.split(',').map(x => x.trim()).filter(x => x !== String(no));
                    if (arr.length) {
                        el.dataset.startNos = arr.join(',');
                        el.setAttribute('data-start-nos', arr.join(','));
                        el.draggable = true;
                        // update title to reflect remaining starts
                        el.title = `#${arr.join(',')}`;
                        setCellVisual(el.dataset.key);
                    } else {
                        delete el.dataset.startNos;
                        el.removeAttribute('data-start-nos');
                        el.draggable = false;
                        el.ondragstart = null;
                        el.title = '';
                        setCellVisual(el.dataset.key);
                    }
                });
            }

            function placeQuestionAt(no, x, y, dir) {
                // remove any previous placement for this question
                clearQuestionFromCells(no);
                const q = questions.find(it => Number(it.no) === Number(no));
                if (!q || !q.answer) return;
                const word = String(q.answer);
                const len = word.length;
                for (let i = 0; i < len; i++) {
                    const cx = dir === 'across' ? x + i : x;
                    const cy = dir === 'down' ? y + i : y;
                    if (cx < 1 || cy < 1 || cx > gridSize || cy > gridSize) continue;
                    const key = `${cx},${cy}`;
                    if (!cellMap[key]) cellMap[key] = {};
                    cellMap[key][no] = word.charAt(i);
                    setCellVisual(key);
                }
                // store starting position
                placed[no] = {
                    no,
                    x: Number(x),
                    y: Number(y),
                    dir
                };
                // mark starting cell with the number and make it draggable to allow move
                const startKey = `${x},${y}`;
                const startCell = document.querySelector(`.cell[data-key="${startKey}"]`);
                if (startCell) {
                    // support multiple start numbers in same cell
                    const current = startCell.dataset.startNos ? startCell.dataset.startNos.split(',') : [];
                    if (!current.includes(String(no))) current.push(String(no));
                    startCell.dataset.startNos = current.join(',');
                    startCell.setAttribute('data-start-nos', current.join(','));
                    startCell.draggable = true;
                    startCell.title = `#${current.join(',')}`;
                    // attach dragstart to allow moving the placed question; drag will carry the question no and its dir
                    startCell.ondragstart = function(e) {
                        // determine which start number user intends: if multiple, default to the largest matching letter? keep simple: use the first number in dataset.startNos
                        const intendedNo = String(no);
                        e.dataTransfer.setData('text/plain', JSON.stringify({
                            no: Number(intendedNo),
                            dir: dir
                        }));
                    };
                    // refresh visual (will include the small startnos by setCellVisual)
                    setCellVisual(startCell.dataset.key);
                }
            }

            function buildGrid(size) {
                gridEl.innerHTML = '';
                // reset cellMap when rebuilding
                for (const k in cellMap) delete cellMap[k];
                gridEl.style.gridTemplateColumns = `repeat(${size}, 28px)`;
                for (let r = 1; r <= size; r++) {
                    for (let c = 1; c <= size; c++) {
                        const cell = document.createElement('div');
                        cell.className = 'cell';
                        cell.dataset.x = c;
                        cell.dataset.y = r;
                        cell.dataset.key = `${c},${r}`;
                        cell.addEventListener('dragover', e => {
                            e.preventDefault();
                            cell.classList.add('drop-hover');
                        });
                        cell.addEventListener('dragleave', e => {
                            cell.classList.remove('drop-hover');
                        });
                        cell.addEventListener('drop', e => {
                            e.preventDefault();
                            cell.classList.remove('drop-hover');
                            const data = e.dataTransfer.getData('text/plain');
                            try {
                                const obj = JSON.parse(data);
                                // place using dir provided by drag source
                                placeQuestionAt(obj.no, parseInt(cell.dataset.x, 10), parseInt(cell.dataset.y, 10), obj.dir || obj.initialDir || 'across');
                            } catch (err) {
                                console.error(err);
                            }
                        });
                        gridEl.appendChild(cell);
                    }
                }
                // reapply placed items (model)
                for (const no in placed) {
                    const p = placed[no];
                    placeQuestionAt(p.no, p.x, p.y, p.dir);
                }
            }

            function renderList() {
                listEl.innerHTML = '';
                questions.forEach(q => {
                    const li = document.createElement('li');
                    li.className = 'draggable-item';
                    li.draggable = true;
                    li.dataset.no = q.no;

                    const left = document.createElement('span');
                    left.textContent = `#${q.no} ${q.question}`;

                    const right = document.createElement('span');
                    const sel = document.createElement('select');
                    sel.className = 'dir-select';
                    sel.dataset.no = q.no;
                    const optA = document.createElement('option');
                    optA.value = 'across';
                    optA.textContent = 'across';
                    const optD = document.createElement('option');
                    optD.value = 'down';
                    optD.textContent = 'down';
                    if (q.dir === 'across') optA.selected = true;
                    else optD.selected = true;
                    sel.appendChild(optA);
                    sel.appendChild(optD);
                    // prevent select from initiating drag
                    sel.addEventListener('mousedown', e => e.stopPropagation());
                    // when direction changes, update model and re-place if already placed
                    sel.addEventListener('change', e => {
                        const newDir = e.target.value;
                        const no = Number(e.target.dataset.no);
                        const item = questions.find(it => Number(it.no) === no);
                        if (item) {
                            item.dir = newDir;
                        }
                        if (placed[no]) {
                            // re-place with same start coordinates but new dir
                            placeQuestionAt(no, placed[no].x, placed[no].y, newDir);
                        }
                    });

                    right.appendChild(sel);
                    li.appendChild(left);
                    li.appendChild(right);

                    li.addEventListener('dragstart', e => {
                        const dirSel = sel;
                        const dir = dirSel ? dirSel.value : q.dir;
                        // include initialDir to fallback
                        e.dataTransfer.setData('text/plain', JSON.stringify({
                            no: q.no,
                            dir,
                            initialDir: q.dir
                        }));
                    });

                    listEl.appendChild(li);
                });
            }

            function savePositions() {
                const arr = [];
                for (const k in placed) {
                    arr.push(placed[k]);
                }
                document.getElementById('positionsInput').value = JSON.stringify(arr);
                document.getElementById('posForm').submit();
            }

            function escapeHtml(s) {
                return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            document.getElementById('gridSize').addEventListener('change', (e) => {
                gridSize = parseInt(e.target.value, 10) || 15;
                buildGrid(gridSize);
            });
            document.getElementById('resetGrid').addEventListener('click', () => {
                for (const k in placed) delete placed[k];
                for (const k in cellMap) delete cellMap[k];
                buildGrid(gridSize);
            });
            document.getElementById('savePosBtn').addEventListener('click', savePositions);

            // initialize preview with current question positions
            (function init() {
                renderList();
                buildGrid(gridSize);
                // apply saved positions from PHP model
                <?php
                // prepare initial placed positions from PHP questions array
                $initPlaced = [];
                foreach ($questions as $q) {
                    if (isset($q['x']) && isset($q['y'])) {
                        $initPlaced[] = ['no' => (int)$q['no'], 'x' => (int)$q['x'], 'y' => (int)$q['y'], 'dir' => $q['dir']];
                    }
                }
                echo "const initialPlaced = " . json_encode($initPlaced) . ";\n";
                ?>
                initialPlaced.forEach(p => {
                    placeQuestionAt(p.no, p.x, p.y, p.dir);
                });
            })();
        </script>
    </body>

    </html>
<?php
    exit;
}

// Jika file ini di-include, kembalikan array soal
return $questions;
?>