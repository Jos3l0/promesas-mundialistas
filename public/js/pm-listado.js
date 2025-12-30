(function () {
  function qs(sel, root) { return (root || document).querySelector(sel); }

  let state = { q: "", page: 1, perPage: (PM_LIST && PM_LIST.per_page) ? PM_LIST.per_page : 12, total: 0 };

  function setDisabled(id, disabled) {
    const el = document.getElementById(id);
    if (el) el.disabled = !!disabled;
  }

  function render(items) {
    const grid = document.getElementById("pm-grid");
    const empty = document.getElementById("pm-empty");
    if (!grid) return;

    grid.innerHTML = "";

    if (!items || !items.length) {
      if (empty) empty.classList.remove("pm-hidden");
      return;
    }
    if (empty) empty.classList.add("pm-hidden");

    items.forEach((it) => {
      const card = document.createElement("div");
      card.className = "pm-card";

      const h = document.createElement("h3");
      h.textContent = `${it.nombre} ${it.apellido}`;
      card.appendChild(h);

      const link = document.createElement("a");
      link.href = it.url;
      link.className = "pm-card-link";

      if (it.image_url) {
        const img = document.createElement("img");
        img.src = it.image_url;
        img.alt = `${it.nombre} ${it.apellido}`;
        img.loading = "lazy";
        link.appendChild(img);
      } else {
        const fallback = document.createElement("div");
        fallback.className = "pm-img-fallback";
        fallback.textContent = "Ver promesa";
        link.appendChild(fallback);
      }

      card.appendChild(link);
      grid.appendChild(card);
    });
  }

  function updatePagination() {
    document.getElementById("pm-page").textContent = String(state.page);
    document.getElementById("pm-total").textContent = String(state.total);

    setDisabled("pm-prev", state.page <= 1);
    const maxPage = Math.max(1, Math.ceil(state.total / state.perPage));
    setDisabled("pm-next", state.page >= maxPage);
  }

  async function load() {
    const url = new URL(PM_LIST.endpoint, window.location.origin);
    if (state.q) url.searchParams.set("q", state.q);
    url.searchParams.set("page", String(state.page));
    url.searchParams.set("per_page", String(state.perPage));

    setDisabled("pm-btn-search", true);
    setDisabled("pm-prev", true);
    setDisabled("pm-next", true);

    const res = await fetch(url.toString(), { credentials: "omit" });
    const json = await res.json().catch(() => ({}));

    if (!json || json.ok !== true) {
      render([]);
      state.total = 0;
      updatePagination();
      setDisabled("pm-btn-search", false);
      return;
    }

    state.total = json.total || 0;
    render(json.items || []);
    updatePagination();
    setDisabled("pm-btn-search", false);
  }

  function doSearch(resetPage) {
    const q = (document.getElementById("pm-q").value || "").trim();
    state.q = q;
    if (resetPage) state.page = 1;
    load();
  }

  document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("pm-btn-search");
    const q = document.getElementById("pm-q");
    const prev = document.getElementById("pm-prev");
    const next = document.getElementById("pm-next");

    if (btn) btn.addEventListener("click", () => doSearch(true));
    if (q) q.addEventListener("keydown", (e) => { if (e.key === "Enter") { e.preventDefault(); doSearch(true); } });

    if (prev) prev.addEventListener("click", () => { state.page = Math.max(1, state.page - 1); load(); });
    if (next) next.addEventListener("click", () => { state.page = state.page + 1; load(); });

    load();
  });
})();