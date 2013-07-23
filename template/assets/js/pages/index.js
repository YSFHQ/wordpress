var Index = function () {
    return {
        initVideos: function () {
			$('#moreVideos li a').click(function() {
				event.preventDefault();
				$('.promo .span6 iframe').attr('src', $(this).attr('href'));
			});
        }
    };
}();