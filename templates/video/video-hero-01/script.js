function vhPlay() {
  var ph = document.getElementById('vhPlaceholder');
  var fr = document.getElementById('vhFrame');
  if (!ph || !fr) return;
  fr.src = fr.getAttribute('data-src');
  ph.style.display = 'none';
  fr.style.display = 'block';
}
