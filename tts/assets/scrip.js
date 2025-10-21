// Shared JS for projector and admin. Provides initProjector() and initAdmin().

function ajaxGet(url) {
  return fetch(url, { cache: "no-store" }).then((r) => r.json());
}
function ajaxPost(url, obj) {
  return fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    cache: "no-store",
    body: JSON.stringify(obj),
  }).then((r) => r.json());
}
// WebAudio beep for sounds
function beep(freq, duration) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = "sine";
    o.frequency.value = freq;
    o.connect(g);
    g.connect(ctx.destination);
    o.start();
    g.gain.setValueAtTime(0.0001, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.01);
    setTimeout(() => {
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.02);
      o.stop();
      ctx.close();
    }, duration);
  } catch (e) {
    console.warn("audio err", e);
  }
}

// small util to create grid DOM
function buildGrid(rows, cols, container) {
  container.innerHTML = "";
  for (let y = 1; y <= rows; y++) {
    for (let x = 1; x <= cols; x++) {
      const cell = document.createElement("div");
      cell.className = "cw-cell";
      cell.id = `cell-${x}-${y}`;
      cell.setAttribute("data-x", x);
      cell.setAttribute("data-y", y);
      // number placeholder and letter span
      const numSpan = document.createElement("div");
      numSpan.className = "cw-number";
      cell.appendChild(numSpan);
      const letterSpan = document.createElement("div");
      letterSpan.className = "cw-letter";
      cell.appendChild(letterSpan);
      container.appendChild(cell);
    }
  }
}

// build sparse grid: buat sel hanya pada koordinat yang dipakai oleh kata/kata
function buildSparseGrid(questions, container) {
  container.innerHTML = "";
  const coords = new Set();
  let maxX = 0,
    maxY = 0;

  questions.forEach((q) => {
    const answer = (q.answer || "").toString();
    const len = answer.length;
    for (let i = 0; i < len; i++) {
      const x = q.x + (q.dir === "across" ? i : 0);
      const y = q.y + (q.dir === "down" ? i : 0);
      coords.add(`${x},${y}`);
      if (x > maxX) maxX = x;
      if (y > maxY) maxY = y;
    }
    // ensure start cell exists for number even if answer length 0
    coords.add(`${q.x},${q.y}`);
    if (q.x > maxX) maxX = q.x;
    if (q.y > maxY) maxY = q.y;
  });

  // set grid columns to the max width so positioning via gridColumnStart works
  container.style.gridTemplateColumns = `repeat(${Math.max(1, maxX)}, 36px)`;

  coords.forEach((k) => {
    const [x, y] = k.split(",").map((n) => parseInt(n, 10));
    const cell = document.createElement("div");
    cell.className = "cw-cell";
    cell.id = `cell-${x}-${y}`;
    cell.style.gridColumnStart = x;
    cell.style.gridRowStart = y;
    cell.setAttribute("data-x", x);
    cell.setAttribute("data-y", y);
    const numSpan = document.createElement("div");
    numSpan.className = "cw-number";
    const letterSpan = document.createElement("div");
    letterSpan.className = "cw-letter";
    cell.appendChild(numSpan);
    cell.appendChild(letterSpan);
    container.appendChild(cell);
  });
}

// helper to place words on sparse grid (only cells created above)
function placeWordsOnSparseGrid(questions, state) {
  // normalize
  const revealedArr =
    state && Array.isArray(state.revealed) ? state.revealed.map(Number) : [];
  const wrongArr =
    state && Array.isArray(state.wrong) ? state.wrong.map(Number) : [];
  const answersObj =
    state && typeof state.answers === "object" && state.answers !== null
      ? state.answers
      : {};
  const currentNo =
    state && (state.current || state.current === 0)
      ? Number(state.current)
      : null;

  // reset numbers and classes (but build letters from map below)
  document.querySelectorAll(".cw-cell").forEach((cell) => {
    cell.classList.remove("revealed", "wrong");
    const num = cell.querySelector(".cw-number");
    if (num) num.textContent = "";
    const l = cell.querySelector(".cw-letter");
    if (l) l.textContent = "";
  });

  // build letters map from all questions that should show letters
  const lettersMap = {}; // key "x,y" => letter
  questions.forEach((q) => {
    const qNo = Number(q.no);
    // read override if present
    let stateAnsRaw = undefined;
    if (answersObj) {
      if (Object.prototype.hasOwnProperty.call(answersObj, q.no))
        stateAnsRaw = answersObj[q.no];
      else if (Object.prototype.hasOwnProperty.call(answersObj, String(q.no)))
        stateAnsRaw = answersObj[String(q.no)];
    }
    const answerFromQ = (q.answer || "").toString().toUpperCase();
    const ans =
      stateAnsRaw !== undefined && stateAnsRaw !== null
        ? String(stateAnsRaw).toUpperCase()
        : answerFromQ;
    const chars = ans.split("");
    const sx = q.x,
      sy = q.y;
    const revealed = revealedArr.includes(qNo);
    const active = currentNo !== null && currentNo === qNo;
    const hasExplicit =
      stateAnsRaw !== undefined &&
      stateAnsRaw !== null &&
      String(stateAnsRaw).length > 0;

    const showLetters = revealed || active || hasExplicit;

    // set start number
    const startCell = document.getElementById(`cell-${sx}-${sy}`);
    if (startCell) startCell.querySelector(".cw-number").textContent = q.no;

    for (let i = 0; i < chars.length; i++) {
      const x = sx + (q.dir === "across" ? i : 0);
      const y = sy + (q.dir === "down" ? i : 0);
      const key = `${x},${y}`;
      // only add to lettersMap if this question should display letters
      if (showLetters) {
        lettersMap[key] = chars[i] === " " ? "" : chars[i];
      }
      // apply classes for revealed/wrong per-cell (revealed/wrong should persist even if letter not shown by other question)
      const cell = document.getElementById(`cell-${x}-${y}`);
      if (!cell) continue;
      if (revealed) cell.classList.add("revealed");
      if (wrongArr.includes(qNo)) cell.classList.add("wrong");
    }
  });

  // apply lettersMap to cells
  document.querySelectorAll(".cw-cell").forEach((cell) => {
    const x = cell.getAttribute("data-x");
    const y = cell.getAttribute("data-y");
    const key = `${x},${y}`;
    const letterSpan = cell.querySelector(".cw-letter");
    if (letterSpan) {
      letterSpan.textContent = lettersMap.hasOwnProperty(key)
        ? lettersMap[key]
        : "";
    }
  });
}

// Projector (updated)
function initProjector(questions) {
  const gridContainer = document.getElementById("crosswordGrid");
  const questionArea = document.getElementById("questionArea");
  const teamsDisplay = document.getElementById("teamsDisplay");
  const lastUpdate = document.getElementById("lastUpdate");
  const cluesAcross = document.getElementById("clues-across") || null;
  const cluesDown = document.getElementById("clues-down") || null;

  // build sparse grid once
  buildSparseGrid(questions, gridContainer);

  // render clue lists
  function renderClues() {
    if (cluesAcross) cluesAcross.innerHTML = "";
    if (cluesDown) cluesDown.innerHTML = "";
    questions.forEach((q) => {
      const el = document.createElement("div");
      el.className = "clue-item";
      el.textContent = q.no + ". " + q.question;
      if (q.dir === "across") {
        if (cluesAcross) cluesAcross.appendChild(el);
      } else {
        if (cluesDown) cluesDown.appendChild(el);
      }
    });
  }
  renderClues();

  let state = null;
  function renderState(s) {
    state = s;
    // place words into sparse grid, using state.answers if present; reveals only those in state.revealed
    placeWordsOnSparseGrid(questions, state);

    // show current question text
    if (s.current) {
      const q = questions.find((x) => x.no == s.current);
      questionArea.textContent = q ? q.question : "Soal tidak ditemukan";
    } else {
      questionArea.textContent = "Pilih nomor soal di admin.";
    }

    // teams
    let teamsHtml = "";
    for (const t in s.teams) {
      teamsHtml += `${t}: ${s.teams[t]} &nbsp;&nbsp;`;
    }
    teamsDisplay.innerHTML = teamsHtml;

    lastUpdate.textContent = new Date(s.lastUpdate * 1000).toLocaleTimeString();
  }

  // Polling (reuse ajaxGet)
  async function poll() {
    try {
      const s = await ajaxGet("api.php?action=getState");
      console.debug("poll state:", s);
      renderState(s);
    } catch (e) {
      console.error(e);
    }
    setTimeout(poll, 900);
  }
  poll();
}

// Admin
// function initAdmin(questions) {
//   const cells = document.querySelectorAll(".admin-cell");
//   let state = null;

//   // read state
//   async function loadState() {
//     state = await ajaxGet("api.php?action=getState");
//     applyStateToUI();
//   }
//   function applyStateToUI() {
//     // update scores
//     document.getElementById("scoreA").value = state.teams["Tim A"];
//     document.getElementById("scoreB").value = state.teams["Tim B"];
//     document.getElementById("scoreC").value = state.teams["Tim C"];
//     document.getElementById("scoreD").value = state.teams["Tim D"];

//     // highlight selected
//     document.querySelectorAll(".admin-cell").forEach((b) => {
//       b.classList.remove("active");
//       if (
//         state.current &&
//         parseInt(b.getAttribute("data-no")) === state.current
//       )
//         b.classList.add("active");
//     });
//   }

// Admin
function initAdmin(questions) {
  const cells = document.querySelectorAll(".admin-cell");
  let state = null;

  // read state
  async function loadState() {
    state = await ajaxGet("api.php?action=getState");
    applyStateToUI();
  }
  function applyStateToUI() {
    // update scores
    document.getElementById("scoreA").value = state.teams["Tim A"];
    document.getElementById("scoreB").value = state.teams["Tim B"];
    document.getElementById("scoreC").value = state.teams["Tim C"];
    document.getElementById("scoreD").value = state.teams["Tim D"];

    // highlight selected, revealed, and wrong questions
    const revealed = state.revealed || [];
    const wrong = state.wrong || [];

    document.querySelectorAll(".admin-cell").forEach((b) => {
      const no = parseInt(b.getAttribute("data-no"));

      // Reset classes
      b.classList.remove("active", "revealed", "wrong");

      // Active question
      if (state.current && no === state.current) {
        b.classList.add("active");
      }

      // Revealed question
      if (revealed.includes(no)) {
        b.classList.add("revealed");
      }

      // Wrong question
      if (wrong.includes(no)) {
        b.classList.add("wrong");
      }
    });
  }

  cells.forEach((b) => {
    b.addEventListener("click", async () => {
      const no = parseInt(b.getAttribute("data-no"));
      state.current = no;
      await ajaxPost("api.php?action=setState", state);
      loadState();
    });
  });

  document.getElementById("revealBtn").addEventListener("click", async () => {
    if (!state.current) return alert("Pilih nomor dulu");
    // add to revealed and add answer text
    if (!state.revealed) state.revealed = [];
    if (!state.answers) state.answers = [];
    if (!state.revealed.includes(state.current))
      state.revealed.push(state.current);
    const q = questions.find((x) => x.no == state.current);
    state.answers[state.current] = q ? q.answer : "";
    await ajaxPost("api.php?action=setState", state);
    // play correct sound
    beep(880, 200);
    beep(1100, 120);
    loadState();
  });

  document.getElementById("markCorrect").addEventListener("click", async () => {
    // default add 10 points to selected team from inputs
    const a = parseInt(document.getElementById("scoreA").value) || 0;
    const b = parseInt(document.getElementById("scoreB").value) || 0;
    const c = parseInt(document.getElementById("scoreC").value) || 0;
    const d = parseInt(document.getElementById("scoreD").value) || 0;
    state.teams = { "Tim A": a, "Tim B": b, "Tim C": c, "Tim D": d };

    // ask which team got it (simple prompt)
    const team = prompt(
      "Masukkan nama tim (Tim A / Tim B / Tim C / Tim D) untuk beri +10",
      "Tim A"
    );
    if (team && state.teams[team] !== undefined) {
      state.teams[team] += 10;
    }

    // also reveal current question
    if (state.current) {
      if (!state.revealed) state.revealed = [];
      if (!state.revealed.includes(state.current))
        state.revealed.push(state.current);
      const q = questions.find((x) => x.no == state.current);
      if (q) {
        state.answers[state.current] = q.answer;
      }
    }

    await ajaxPost("api.php?action=setState", state);
    // correct sound
    beep(880, 200);
    beep(1100, 120);
    loadState();
  });

  document.getElementById("markWrong").addEventListener("click", async () => {
    if (!state.current) return alert("Pilih nomor dulu");
    // mark wrong visually by adding a wrong list in state
    if (!state.wrong) state.wrong = [];
    if (!state.wrong.includes(state.current)) state.wrong.push(state.current);
    await ajaxPost("api.php?action=setState", state);
    // wrong sound
    beep(200, 300);
    beep(160, 120);
    loadState();
  });

  document.getElementById("resetBtn").addEventListener("click", async () => {
    if (!confirm("Reset semua state?")) return;
    const defaultState = {
      current: null,
      revealed: [],
      answers: [],
      teams: { "Tim A": 0, "Tim B": 0, "Tim C": 0, "Tim D": 0 },
    };
    await ajaxPost("api.php?action=setState", defaultState);
    loadState();
  });

  // manual score change -> push to state
  ["scoreA", "scoreB", "scoreC", "scoreD"].forEach((id) => {
    document.getElementById(id).addEventListener("change", async () => {
      state.teams["Tim A"] =
        parseInt(document.getElementById("scoreA").value) || 0;
      state.teams["Tim B"] =
        parseInt(document.getElementById("scoreB").value) || 0;
      state.teams["Tim C"] =
        parseInt(document.getElementById("scoreC").value) || 0;
      state.teams["Tim D"] =
        parseInt(document.getElementById("scoreD").value) || 0;
      await ajaxPost("api.php?action=setState", state);
    });
  });

  loadState();
}
