$(function () {
  $("[data-treevalue]").hover(function(){
        $('[data-treevalue-img='+$(this).data('treevalue')+']').toggle();
   });

});

window.addEventListener('load', function() {
	$('#header .phone-block a').addClass('comagic_phone');
    $('#footer .phone.blocks a').addClass('comagic_phone');
    $('.mobile-menu-contacts a').addClass('comagic_phone');
    $('#mobilePhone  .more_phone a').addClass('comagic_phone2');
    
    var contact_url = window.location.href;
   
    if(contact_url == 'https://dibidishop.ru/contacts/') {
         $('.property.phone .value.darken').addClass('comagic_phone3');
    }
});