(function () {
  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function getGeneralBox() {
    // ✅ #pm-general-error está FUERA del <form> en el template
    return document.getElementById("pm-general-error");
  }

  function clearErrors(form) {
    qsa(".pm-field", form).forEach((w) => w.classList.remove("pm-has-error"));
    qsa(".pm-error", form).forEach((e) => (e.textContent = ""));

    const gen = getGeneralBox();
    if (gen) {
      gen.textContent = "";
      gen.classList.add("pm-hidden");
    }
  }

  function setGeneralError(msg) {
    const gen = getGeneralBox();
    if (!gen) return;
    gen.textContent = msg;
    gen.classList.remove("pm-hidden");
  }

  function setFieldError(field, msg) {
    const wrap = field && field.closest ? field.closest(".pm-field") : null;
    if (!wrap) return;
    wrap.classList.add("pm-has-error");
    const help = qs(".pm-error", wrap);
    if (help) help.textContent = msg;
  }

  function setLoading(form, on) {
    const btn = qs('button[type="submit"]', form);
    if (!btn) return;
    btn.disabled = !!on;
  }

  function serialize(form) {
    return {
      first_name: (qs('[name="first_name"]', form)?.value || "").trim(),
      last_name: (qs('[name="last_name"]', form)?.value || "").trim(),
      alias: (qs('[name="alias"]', form)?.value || "").trim(),
      instagram: (qs('[name="instagram"]', form)?.value || "").trim(),
      condicion: (qs('[name="condicion"]', form)?.value || "").trim(),
      promesa_texto: (qs('[name="promesa_texto"]', form)?.value || "").trim(),
    };
  }

  function showSuccess(form, url) {
    const box = qs("#pm-success", form);
    if (!box) return;
    box.classList.remove("pm-hidden");

    const link = qs("#pm-success-link", box);
    if (link) { link.href = url; link.textContent = url; }

    const copyBtn = qs("#pm-copy-link", box);
    if (copyBtn) {
      copyBtn.onclick = function () {
        if (navigator.clipboard && url) navigator.clipboard.writeText(url);
      };
    }
  }

  function updateCounter(form) {
    const ta = qs('[name="promesa_texto"]', form);
    const counter = qs("#pm-char-counter", form);
    if (!ta || !counter) return;
    counter.textContent = `${(ta.value || "").length}/500`;
  }

  async function submitForm(e) {
    e.preventDefault();
    const form = e.currentTarget;

    clearErrors(form);
    setLoading(form, true);

    const payload = serialize(form);

    try {
      const res = await fetch(PM_REG.endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        credentials: "omit",
      });

      const json = await res.json().catch(() => ({}));

      if (!res.ok || !json || json.ok !== true) {
        const errs = (json && json.errors) ? json.errors : { general: "No se pudo enviar. Probá de nuevo." };

        // ✅ Mostrar TODOS los errores juntos arriba (general)
        const messages = [];
        Object.keys(errs).forEach((k) => { if (errs[k]) messages.push(errs[k]); });
        if (messages.length) setGeneralError(messages.join(" | "));

        // ✅ Marcar campos específicos (keys reales del backend)
        Object.keys(errs).forEach((k) => {
          const msg = errs[k];
          if (!msg) return;

          if (k === "first_name") setFieldError(qs('[name="first_name"]', form), msg);
          else if (k === "last_name") setFieldError(qs('[name="last_name"]', form), msg);
          else if (k === "alias") setFieldError(qs('[name="alias"]', form), msg);
          else if (k === "instagram") setFieldError(qs('[name="instagram"]', form), msg);
          else if (k === "condicion") setFieldError(qs('[name="condicion"]', form), msg);
          else if (k === "promesa_texto") setFieldError(qs('[name="promesa_texto"]', form), msg);
        });

        return;
      }

      showSuccess(form, json.url);
      form.reset();
      updateCounter(form);

    } catch (err) {
      setGeneralError("Ocurrió un error inesperado. Probá nuevamente.");
    } finally {
      setLoading(form, false);
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("pm-form");
    if (!form) return;

    const ta = qs('[name="promesa_texto"]', form);
    if (ta) ta.addEventListener("input", () => updateCounter(form));
    updateCounter(form);

    form.addEventListener("submit", submitForm);
  });
})();