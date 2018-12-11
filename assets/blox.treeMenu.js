
/**
 * @todo Open tree by default
 */
function initMenus()
{
  if ( ! document.getElementsByTagName )  {return;}
  var singleopen, keepopen
  var lists = document.getElementsByTagName("ul");
  for ( var i = 0; i < lists.length; i++ ) {
    if (i==0)
        initMenu(lists[i], elementHasClass(lists[i], "singleopen"), elementHasClass(lists[i], "keepopen"));
  }
}



function initMenu(list, singleopen, keepopen)
{
  var item, a, open;
  var items = getChildNodes(list, "li");
  open = false;
  for (var i = 0; i<items.length; i++) {
    item = items[i];
    var subList = getChildNodes(item, "ul")[0];
    if (subList) {
      a = createSwitchButton(item);
      open = initMenu(subList, singleopen, keepopen) || open;
      a.onclick = function() {return menuonclick(this, singleopen);}
    } else {
      createEmptyButton(item);
      if (a) open = open || (keepopen && a.href == window.location);
    }
    if (item.className == "treenodeopen") setMenu(item, true);
    open = open || item.className == "treenodeshow";
  }
  items[items.length-1].className = "last";
  setMenu(list.parentNode, open);
  return open;
}


function menuonclick(a, singleopen)
{
  setMenu(a.parentNode, a.className == "treeclosed");
  var lists = getChildNodes(a.parentNode.parentNode, "li");
  if (singleopen) {
    for (var i = 0; i<lists.length; i++)
      if (lists[i] != a.parentNode) {setMenu(lists[i], false);}
  }
  return false;
}



function createSwitchButton(listItem)
{
  var a = document.createElement("a");
  a.href = "#";
  var textNode = document.createTextNode("\u00A0\u00A0\u00A0\u00A0\u00A0")
  a.appendChild(textNode);
  var fc = listItem.firstChild;
  listItem.insertBefore(a, fc);
  return a;
}



function createEmptyButton(listItem)
{
  var span = document.createElement("span");
  span.className = "list-item";
  var textNode = document.createTextNode("\u00A0\u00A0\u00A0\u00A0\u00A0")
  span.appendChild(textNode)
  var fc = listItem.firstChild;
  listItem.insertBefore(span, fc)
}



function setMenu(list, open)
{
  var a = getChildNodes(list, "a")[0];
  var ul = getChildNodes(list, "ul")[0];
  if (a && ul) {
    if (open) {
      a.className = "treeopen";
      ul.style.display = "block";
    } else {
      a.className = "treeclosed";
      ul.style.display = "none";
    }
  }
}


function elementHasClass( element, className )
{
  if ( ! element.className )  { return false;}
  var re = new RegExp( "(^|\\s+)" + className + "($|\\s+)" );
  return re.exec( element.className );
}



function getChildNodes(element, tag)
{
  var foundNodes = new Array();
  var childNodes = element.childNodes;
  for (var i = 0; i < childNodes.length; i++ ) {
    var node = childNodes[i];
    if (node.tagName && (node.tagName.toLowerCase() == tag))
      foundNodes.push(node);
  }
  return foundNodes;
}
