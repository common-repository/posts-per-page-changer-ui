function overrideQuery() {

  var obj = document.postsperpageChanger.linkselect;

  var index = obj.selectedIndex;
  var href = obj.options[index].value;

  location.href = href;
}