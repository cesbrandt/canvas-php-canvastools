function ready(func) {
  if(typeof func !== 'function') {
    return;
  }

  if(document.readyState === 'complete') {
    return func();
  }

  document.addEventListener('DOMContentLoaded', func, false);
}

function classAdd() {
  footer(this);
  this.classList.add('open');
}

function classRemove() {
  footer(this);
  this.classList.remove('open');
  this.classList.add('close');
}

var footerEL = false;
function footer(elem) {
  var elem = (elem.nodeType !== 1) ? document.getElementsByTagName('footer')[0] : elem;
  elem.style.bottom = 'calc(0px - (' + elem.offsetHeight + 'px - 1em))';
  if(!footerEL) {
    elem.resize(footer);
    elem.addEventListener('mouseover', classAdd);
    elem.addEventListener('mouseout', classRemove);
    footerEL = true;
  }
}

Element.prototype.resize = function(func) {
  window.addEventListener('resize', func);
};

Element.prototype.marginHeight = function() {
  var elemMargin;
  if(document.all) {
    var cur = this.currentStyle;
    elemMargin = parseInt(cur.marginTop, 10) + parseInt(cur.marginBottom, 10);
  } else {
    var view = document.defaultView;
    elemMargin = parseInt(view.getComputedStyle(this, '').getPropertyValue('margin-top'), 10) + parseInt(view.getComputedStyle(this, '').getPropertyValue('margin-bottom'), 10);
  }
  return elemMargin;
};

Element.prototype.totalHeight = function() {
  var elemHeight, elemMargin;
  if(document.all) {
    var cur = this.currentStyle;
    elemHeight = parseInt(cur.height, 10);
    elemMargin = parseInt(cur.marginTop, 10) + parseInt(cur.marginBottom, 10);
  } else {
    var view = document.defaultView;
    elemHeight = parseInt(view.getComputedStyle(this, '').getPropertyValue('height'), 10);
    elemMargin = parseInt(view.getComputedStyle(this, '').getPropertyValue('margin-top'), 10) + parseInt(view.getComputedStyle(this, '').getPropertyValue('margin-bottom'), 10);
  }
  return elemHeight + elemMargin;
};

ready(function() {
  document.getElementsByTagName('main')[0].style.height = 'calc(100% - ' + document.getElementsByTagName('header')[0].totalHeight() + 'px - ' + document.getElementsByTagName('header')[0].marginHeight() + 'px - 1em)';
  footer(document.getElementsByTagName('footer')[0]);
});