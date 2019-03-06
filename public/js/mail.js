$(document).ready(function() {
	$('#responseMessage').remove();
	$("#contactForm").submit(function(event) {
		event.preventDefault();
		$('input#submit').addClass('disabled');
		$('#responseMessage').remove();
		console.log($(this).serialize());
		$.post("contattami", $(this).serialize(), function(response){
			if(response.status === 1){
				$('#contactForm').prepend("<div id='responseMessage' class='success'><h4>Messaggio inviato correttamente!</h4><p>Ti contatter&ograve; appena possibile.</p></div>");
				setTimeout(function() {
					$('#responseMessage').fadeOut();
					$('input#submit').removeClass('disabled');
				}, 5000);
			}else if(response.status === 0){
				console.log(response);
				$('#contactForm').prepend("<div id='responseMessage' class='error'><h4>Qualcosa &egrave; andato storto!</h4></div>");
				if(response.name !== false){
					$('#responseMessage').append('<p>'+response.name+'</p><br>');
				}
				if(response.email !== false){
					$('#responseMessage').append('<p>'+response.email+'</p><br>');
				}
				if(response.message !== false){
					$('#responseMessage').append('<p>'+response.message+'</p>');
				}
				$('input#submit').removeClass('disabled');
			}
		}, "json").fail(function() {
			$('#contactForm').prepend("<div id='responseMessage' class='error'><h4>Qualcosa &egrave; andato storto!</h4></div>");
			$('input#submit').removeClass('disabled');
		});
		return false;
	});
});
