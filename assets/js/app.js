// theme toggle
(function () {
  const btn = document.getElementById("theme-toggle");
  btn &&
    btn.addEventListener("click", () => {
      document.body.classList.toggle("theme-dark");
      document.body.classList.toggle("theme-light");
      localStorage.setItem(
        "theme",
        document.body.classList.contains("theme-dark") ? "dark" : "light"
      );
    });
  const saved = localStorage.getItem("theme");
  if (saved === "dark") document.body.classList.add("theme-dark");
})();
