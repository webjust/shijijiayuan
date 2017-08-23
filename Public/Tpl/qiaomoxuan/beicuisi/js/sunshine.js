var sunshine = sunshine || {};

sunshine.printCoupon = function(image) {
  var pwin = window.open(image.src);
  pwin.print();
}

sunshine.setHeight =  function(container, full, offset){
  var realHeight = $(container).height();
  var height = $(full).height() - offset;

  if (realHeight<height) {
    $(container).height(height);
  };
}

sunshine.verifyInput = function(input, alert){
  $(input).focus(function(){
    $(alert).css('display', 'none');
  });

  var name = $(input).val();
  if (name == '' || name == null) {
    $(alert).css({
      'display':'inline-block',
      '*display': 'inline',
      '*zoom':'1'
    });
    return;
  };
}