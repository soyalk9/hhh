(() => {
  const toggle = document.getElementById('themeToggle');
  if (!toggle) return;
  if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
  toggle.addEventListener('click', () => {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
  });
})();
