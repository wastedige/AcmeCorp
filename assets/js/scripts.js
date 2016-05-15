
jQuery(document).ready(function() {

	/*
	    Wow
	*/
	new WOW().init();

	/*
	    Slider
	*/
	$('.flexslider').flexslider({
        animation: "slide",
        controlNav: "thumbnails",
        prevText: "",
        nextText: ""
    });

	/*
	    Slider 2
	*/
	$('.slider-2-container').backstretch([

	  "assets/img/slider/5.jpg"
	, "assets/img/slider/6.jpg"
	, "assets/img/slider/7.jpg"
	, "assets/img/slider/8.jpg"
	], {duration: 3000, fade: 750});

	/*
	    Image popup (home latest work)
	*/
	$('.view-work').magnificPopup({
		type: 'image',
		gallery: {
			enabled: true,
			navigateByImgClick: true,
			preload: [0,1] // Will preload 0 - before current, and 1 after the current image
		},
		image: {
			tError: 'The image could not be loaded.',
			titleSrc: function(item) {
				return item.el.parent('.work-bottom').siblings('img').attr('alt');
			}
		},
		callbacks: {
			elementParse: function(item) {
				item.src = item.el.attr('href');
			}
		}
	});

	/*
	    Flickr feed
	*/
	$('.flickr-feed').jflickrfeed({
        limit: 8,
        qstrings: {
            id: '52617155@N08'
        },
        itemTemplate: '<a href="{{link}}" target="_blank" rel="nofollow"><img src="{{image_s}}" alt="{{title}}" /></a>'
    });

	/*
	    Google maps
	*/
	var position = new google.maps.LatLng(43.0335766, -88.0808318);
    $('.map').gmap({'center': position,'zoom': 16, 'disableDefaultUI':true, 'callback': function() {
            var self = this;
            self.addMarker({'position': this.get('map').getCenter() });
        }
    });



    /*
	    Contact form
	*/
    $('.contact-form form').submit(function(e) {
    	e.preventDefault();

    	var form = $(this);
    	var nameLabel = form.find('label[for="contact-name"]');
    	var emailLabel = form.find('label[for="contact-email"]');
    	var messageLabel = form.find('label[for="contact-message"]');
		var phoneLabel = form.find('label[for="contact-phone"]');
		var processing = form.find('label[for="processing"]');
		var sendButton = form.find($('.submit-message'));
		var captchaLabel = form.find('label[for="contact-captcha"]');



    	nameLabel.html('Name');
    	emailLabel.html('Email');
    	messageLabel.html('Message');
    	phoneLabel.html('Phone');
    	captchaLabel.html('');


        // Temporarily disable Send button and show a message
        // This action is undone if there's an issue with the content
    	sendButton.hide();
    	processing.html('Please wait...');

        $.ajax({
            type: 'POST',
            url: 'assets/sendmail.php',
            dataType: 'json',
						data:  new FormData(this),
						contentType: false,
						cache: false,
						processData:false,
						error:function(x,e) {
						    if (x.status==0) {
						        alert('You are offline!!\n Please Check Your Network.');
						    } else if(x.status==404) {
						        alert('Requested URL not found.');
						    } else if(x.status==500) {
						        alert('Internel Server Error.');
						    } else if(e=='parsererror') {
						        alert('Error.\nParsing JSON Request failed.');
						    } else if(e=='timeout'){
						        alert('Request Time out.');
						    } else {
						        alert('Unknow Error.\n'+x.responseText);
						    }
						},
            success: function(json) {
                if(json.nameMessage != '') {
                	nameLabel.append(' - <span class="violet error-label"> ' + json.nameMessage + '</span>');
                }
                if(json.emailMessage != '') {
                	emailLabel.append(' - <span class="violet error-label"> ' + json.emailMessage + '</span>');
                }
                if(json.messageMessage != '') {
                	messageLabel.append(' - <span class="violet error-label"> ' + json.messageMessage + '</span>');
                }
				if(json.phoneMessage != '') {
                	phoneLabel.append(' - <span class="violet error-label"> ' + json.phoneMessage + '</span>');
				}
				if (json.captchaMessage != '') {
				    captchaLabel.append(' - <span class="violet error-label"> ' + json.captchaMessage + '</span>');
				}

                if(json.nameMessage == '' && json.emailMessage == '' && json.messageMessage == '' && json.phoneMessage == '' && json.captchaMessage == '') {
                	form.fadeOut('fast', function() {
                		form.parent('.contact-form').append('<p><span class="violet">Thanks for contacting us!</span> We will get back to you very soon.</p>');
                    });
                } else {
                    // Issues found
                    // Show the send button and erase the 'please wait' label
                    processing.html('');
                    sendButton.show();
                }
            }
        });
    });

});


jQuery(window).load(function() {

	/*
	    Portfolio
	*/
	$('.portfolio-masonry').masonry({
		columnWidth: '.portfolio-box',
		itemSelector: '.portfolio-box',
		transitionDuration: '0.5s'
	});

	$('.portfolio-filters a').on('click', function(e){
		e.preventDefault();
		if(!$(this).hasClass('active')) {
	    	$('.portfolio-filters a').removeClass('active');
	    	var clicked_filter = $(this).attr('class').replace('filter-', '');
	    	$(this).addClass('active');
	    	if(clicked_filter != 'all') {
	    		$('.portfolio-box:not(.' + clicked_filter + ')').css('display', 'none');
	    		$('.portfolio-box:not(.' + clicked_filter + ')').removeClass('portfolio-box');
	    		$('.' + clicked_filter).addClass('portfolio-box');
	    		$('.' + clicked_filter).css('display', 'block');
	    		$('.portfolio-masonry').masonry();
	    	}
	    	else {
	    		$('.portfolio-masonry > div').addClass('portfolio-box');
	    		$('.portfolio-masonry > div').css('display', 'block');
	    		$('.portfolio-masonry').masonry();
	    	}
		}
	});

	$(window).on('resize', function(){ $('.portfolio-masonry').masonry(); });

	// image popup
	$('.portfolio-box h3').magnificPopup({
		type: 'image',
		gallery: {
			enabled: true,
			navigateByImgClick: true,
			preload: [0,1] // Will preload 0 - before current, and 1 after the current image
		},
		image: {
			tError: 'The image could not be loaded.',
			titleSrc: function(item) {
				return item.el.text();
			}
		},
		callbacks: {
			elementParse: function(item) {
				var box_container = item.el.parents('.portfolio-box-container');
				if(box_container.hasClass('portfolio-video')) {
					item.type = 'iframe';
					item.src = box_container.data('portfolio-big');
				}
				else {
					item.type = 'image';
					item.src = box_container.find('img').attr('src');
				}
			}
		}
	});

	/*
		Hidden images
	*/
	$(".testimonial-image img, .portfolio-box img").attr("style", "width: auto !important; height: auto !important;");

});
