function show(layer) {
  var l = document.getElementById(layer);
  l.style.display = "block";
}
function hide(layer) {
  var l = document.getElementById(layer);
  l.style.display = "none";
}

function toggle_layer(layer,img) {
  var l = document.getElementById(layer);
  var i = document.getElementById(img);
  if (l.style.display == "none") {
    l.style.display = "block";
    i.src = "gifs/close.gif";
  }
  else {
    l.style.display = "none";
    i.src = "gifs/open.gif";
  }
}
