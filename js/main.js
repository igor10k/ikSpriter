/*global ZeroClipboard:false */

$(function () {
	var $sendBtn = $("#send");

	var sendBtnDefaultText = $sendBtn.html();
	var sendBtnProcessingText = $sendBtn.data("processingtext");

	var $errorsEl = $("#errors");
	var $spriteEl = $("#sprite");
	var $previewEl = $("#preview");
	var $cssEl = $("#css");
	var $infoEl = $("#info");
	var $oldSizeEl = $("#old_size");
	var $newSizeEl = $("#new_size");
	var $widthEl = $("#width");
	var $heightEl = $("#height");

	var clip;

	$("[name='type']").on("change", function () {
		if (this.value === "lessm") {
			$("#group").attr("disabled", "disabled");
		} else {
			$("#group").removeAttr("disabled");
		}
	});

	$sendBtn.on("click", function (event) {
		event.preventDefault();

		if ($sendBtn.is(":disabled")) {
			return;
		}

		if (clip) {
			clip.destroy();
		}

		$spriteEl.hide().empty();
		$previewEl.removeClass("shown").height("").empty();
		$cssEl.hide().empty();
		$oldSizeEl.empty();
		$newSizeEl.empty();
		$widthEl.empty();
		$heightEl.empty();

		// $sendBtn.attr("disabled", "disabled").html(sendBtnProcessingText);

		var fd = new FormData($("#form")[0]);

		$.ajax({
			url: window.location.protocol + "//" + window.location.hostname + window.location.pathname.substr(0, window.location.pathname.lastIndexOf("/")) + "/api.php",
			type: "POST",
			data: fd,
			cache: false,
			processData: false,
			contentType: false,
			dataType: "json",
			success: function (data) {
				if (data.error) {
					$errorsEl.show().html(data.msg);
				} else {
					$errorsEl.hide();
					var $showImgBtn = $("<button id=\"show_img\">+</button>");

					$spriteEl.show().html("<a href=\"" + data.url + "\">" + data.url + "</a>").append($showImgBtn);
					$cssEl.show().html(data.css);
					$infoEl.show();
					$oldSizeEl.html((data.oldSize / 1000) + "KB");
					$newSizeEl.html((data.newSize / 1000) + "KB");
					$widthEl.html(data.width + "px");
					$heightEl.html(data.height + "px");

					var $copyCSSBtn = $("<button class=\"copy_css_wrap\"/>");
					$copyCSSBtn.append("<span id=\"copy_css\">⎘</span>");

					$cssEl.before($copyCSSBtn);

					clip = new ZeroClipboard.Client();
					clip.setText(data.css);
					clip.setHandCursor(true);
					clip.glue("copy_css");
					clip.addEventListener("onComplete", function () {
						$copyCSSBtn.addClass("active");
					});

					$(window).resize(function () {
						clip.reposition();
					});

					var $img = $("<img src=\"" + data.url + "\" width=\"" + data.width + "\" height=\"" + data.height + "\" style=\"margin-left:-" + parseInt(data.width / 2, 10) + "px;\">");
					$previewEl.empty().append($img);

					$img.on("click", function () {
						$(this).toggleClass("dark");
					});

					var showImgBtnTimeout;

					$showImgBtn.on("click", function (event) {
						event.preventDefault();

						if (! $showImgBtn.hasClass("active")) {
							setTimeout(function () {
								$previewEl.addClass("shown").height($img.height());
								$img.addClass("shown");
							}, 10);
							clearTimeout(showImgBtnTimeout);
							showImgBtnTimeout = setTimeout(function () {
								clip.reposition();
							}, 1100);

							$showImgBtn.addClass("active").html("-");
						} else {
							setTimeout(function () {
								$previewEl.removeClass("shown").height("");
								$img.removeClass("shown");
							}, 10);
							clearTimeout(showImgBtnTimeout);
							showImgBtnTimeout = setTimeout(function () {
								clip.reposition();
							}, 1020);

							$showImgBtn.removeClass("active").html("+");
						}
					});
				}

				$sendBtn.removeAttr("disabled").html(sendBtnDefaultText);
			}
		});
	});
});
