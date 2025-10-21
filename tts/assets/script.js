// Minimal JS untuk input soal, auto‑place dan preview

(function () {
  const qType = document.getElementById("qType");
  const mcSection = document.getElementById("mcSection");
  const fillSection = document.getElementById("fillSection");
  const optionsList = document.getElementById("optionsList");
  const addOption = document.getElementById("addOption");
  const previewBtn = document.getElementById("previewBtn");
  const preview = document.getElementById("preview");
  const saveBtn = document.getElementById("saveBtn");
  const fillText = document.getElementById("fillText");
  const fillAnswers = document.getElementById("fillAnswers");

  let optionId = 0;

  function createOption(text = "", checked = false) {
    const wrap = document.createElement("div");
    wrap.className = "option";
    wrap.draggable = true;
    wrap.dataset.id = ++optionId;

    const radio = document.createElement("input");
    radio.type = "radio";
    radio.name = "correct";
    radio.checked = checked;

    const input = document.createElement("input");
    input.type = "text";
    input.placeholder = "Teks opsi";
    input.value = text;

    const rem = document.createElement("button");
    rem.type = "button";
    rem.className = "remove";
    rem.textContent = "Hapus";
    rem.onclick = () => wrap.remove();

    wrap.appendChild(radio);
    wrap.appendChild(input);
    wrap.appendChild(rem);

    // drag handlers
    wrap.addEventListener("dragstart", (e) => {
      e.dataTransfer.setData("text/plain", wrap.dataset.id);
      wrap.style.opacity = "0.5";
    });
    wrap.addEventListener("dragend", () => (wrap.style.opacity = ""));
    wrap.addEventListener("dragover", (e) => e.preventDefault());
    wrap.addEventListener("drop", (e) => {
      e.preventDefault();
      const id = e.dataTransfer.getData("text/plain");
      const dragged = [...optionsList.children].find(
        (n) => n.dataset.id === id
      );
      if (dragged) optionsList.insertBefore(dragged, wrap);
    });

    optionsList.appendChild(wrap);
    return wrap;
  }

  function collectOptions() {
    const nodes = [...optionsList.querySelectorAll(".option")];
    return nodes.map((n) => {
      return {
        text: n.querySelector('input[type="text"]').value,
        correct: n.querySelector('input[type="radio"]').checked,
      };
    });
  }

  addOption.onclick = () => createOption();

  qType.onchange = () => {
    if (qType.value === "mc") {
      mcSection.style.display = "block";
      fillSection.style.display = "none";
    } else {
      mcSection.style.display = "none";
      fillSection.style.display = "block";
      buildFillAnswers();
    }
  };

  function shuffle(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  }

  function buildFillAnswers() {
    const text = fillText.value || "";
    const ids = [...text.matchAll(/\[\[(\d+)\]\]/g)].map((m) => m[1]);
    fillAnswers.innerHTML = "";
    ids.forEach((id) => {
      const div = document.createElement("div");
      div.innerHTML = `<label>Jawaban [[${id}]] <input data-id="${id}" type="text" placeholder="jawaban ${id}"></label>`;
      fillAnswers.appendChild(div);
    });
  }

  fillText.addEventListener("input", buildFillAnswers);

  previewBtn.onclick = () => {
    preview.innerHTML = "";
    const type = qType.value;
    if (type === "mc") {
      const auto = document.getElementById("mcAuto").checked;
      let opts = collectOptions();
      if (opts.length === 0) {
        preview.textContent = "Belum ada opsi.";
        return;
      }
      if (auto) opts = shuffle(opts.slice());
      opts.forEach((o, idx) => {
        const d = document.createElement("div");
        d.className = "choice";
        d.textContent = `${String.fromCharCode(65 + idx)}. ${o.text}`;
        if (o.correct) d.style.borderColor = "#2ecc71";
        preview.appendChild(d);
      });
    } else {
      const text = fillText.value || "";
      const auto = document.getElementById("fillAuto").checked;
      const answers = {};
      [...fillAnswers.querySelectorAll("input")].forEach(
        (i) => (answers[i.dataset.id] = i.value)
      );
      // render text: replace placeholders with blanks or answers
      const rendered = text.replace(/\[\[(\d+)\]\]/g, (m, id) => {
        const ans = answers[id] || "";
        if (auto) return `<span class="blank">${ans}</span>`;
        return `<span class="blank">&nbsp;&nbsp;&nbsp;</span>`;
      });
      preview.innerHTML = rendered;
    }
  };

  saveBtn.onclick = () => {
    const payload = {
      title: document.getElementById("qTitle").value,
      type: qType.value,
    };
    if (qType.value === "mc") {
      payload.options = collectOptions();
      payload.auto = document.getElementById("mcAuto").checked;
    } else {
      const ids = [...fillText.value.matchAll(/\[\[(\d+)\]\]/g)].map(
        (m) => m[1]
      );
      payload.text = fillText.value;
      payload.answers = {};
      ids.forEach((id) => {
        const v = fillAnswers.querySelector(`input[data-id="${id}"]`);
        payload.answers[id] = v ? v.value : "";
      });
      payload.auto = document.getElementById("fillAuto").checked;
    }
    // simpan di localStorage sebagai contoh
    const store = JSON.parse(localStorage.getItem("questions") || "[]");
    store.push(payload);
    localStorage.setItem("questions", JSON.stringify(store));
    alert("Soal disimpan (localStorage).");
  };

  // inisialisasi dua opsi contoh
  createOption("Contoh A", true);
  createOption("Contoh B", false);
  buildFillAnswers();
})();
