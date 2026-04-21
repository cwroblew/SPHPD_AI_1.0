// alert('Loaded');

function doPop(htmlFile, width, height, scrolls)
{
  var  myWin = "" ;
  var      x = screen.width ;
  var      y = screen.height ;
  var    top = parseInt( ( y - height ) / 2 ) ;
  var   left = parseInt( ( x - width  ) / 2 ) ;

  if (scrolls == "") {
      scrolls = "no";
  }


  var  props = "toolbars=no,status=no,resizable=no, scrollbars=" + scrolls + ", width=" + width + ",height=" + height + ",left=" + left + ",top=" + top;

  if (width == undefined && height == undefined) {
      props = "toolbars=no,status=yes,resizable=yes, scrollbars=yes";
  }

  // alert('Opening window = ' + htmlFile );

  myWin = window.open( htmlFile , "myWin" , props ) ;
  myWin.status = "Please close the window when done.";
  myWin.focus();

}
