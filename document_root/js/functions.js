	// var kQuiz = {	// 		// 	// 	kQuiz: function () { return this; }	// 		// };	var opera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;	var ff = (navigator.userAgent.indexOf("Firefox") != -1) ? true : false;	var msie = /*@cc_on!@*/false;	var msieold = false;	if (msie) {		msieold = (typeof window.XMLHttpRequest == -1) ? false : true;	}			var kQuiz = function(config) {	var kQuiz = { 						updateSnippet: function (id, html) {				$("#" + id).html(html);			},			submitCallback: function (response) {				kQuiz.form_submitted = true;								return true;			}, // End of callback						submit: function () {				var send_values = {};				var values = $(kQuiz.form).serializeArray();				// todo lepsi selektor cez childs formu				$('#qform :input').attr('disabled', true);								for (var i = 0; i < values.length; i++) {					var name = values[i].name;					// multi					if (name in send_values) {						var val = send_values[name];						if (!(val instanceof Array)) {							val = [val];						}						val.push(values[i].value);						send_values[name] = val;					} else {						send_values[name] = values[i].value;					}				}				jQuery.ajax({					url: $(kQuiz.form).attr("action"),					data: send_values,					type: $(kQuiz.form).attr("method"),					success: kQuiz.submitCallback				});								return true;			}, // End of processAnswer			quizCountDown: function () {				var elm = $("#quiz-countdown"); // todo toto je blbost na entu				var timer_val = parseInt(elm.html());				if ( timer_val != 0  && !isNaN(timer_val)) {					elm.html(timer_val - 1)				} else {					clearInterval(kQuiz.cdi); // Queiz CountDownInterval					$.getJSON(kQuiz.url, null, kQuiz.initData);				}				return true;			}, // End of countDown						questionCountDown: function () {				var elm = $("#question-timer"); // todo toto je blbost na entu				var timer_val = parseInt(elm.html());								if ( timer_val != 0  && !isNaN(timer_val)) {					elm.html(timer_val - 1)				} else {					clearInterval(kQuiz.qcdi); // questionCountDownInterval				}								return true;			}, // End of countDown												initData: function (data) {				if (data.snippets) {					for (var i in data.snippets ) {						kQuiz.updateSnippet(i, data.snippets[i]);					}		 		}								if ( data.quiz ) 				{					var q = data.quiz;					if ( q.run == 1 )					{						if ( typeof(kQuiz.run) != "undefined" || kQuiz.run == 0 ) {							kQuiz.run = 1;						} else {							if ( data.question ) {								clearInterval(kQuiz.questionTimerInterval);								kQuiz.initQuestion(data.question);							}						}					} 					else if ( q.time < 300 ) 					{						$("#quiz-countdown").html(q.time);						kQuiz.cdi = setInterval(kQuiz.quizCountDown, 1000);					}					else 					{					 //  kviz nebezi a zaroven je predpokladany cas do spustenia viac ako 5 minut poslem request za polovicu stavajuceho casu						setTimeout(function() { $.getJSON(kQuiz.url, null, kQuiz.initData); }, Math.round( (q.time * 1000) / 2) );					}				}				return true;			}, // End of initData						questionUpdate: function () {				clearInterval(kQuiz.hint_int);				if ( !kQuiz.form_submitted ) 				{					if ( kQuiz.question.type == "multi" )					{						if ( $("#qform :input:checkbox:checked").length != 0 )						{							kQuiz.submit();						}					}				}								kQuiz.getAnswer();			},			bindHint: function (response) {				if (response.hint) {					if ( kQuiz.question.type == "multi" )					{						var elms = $("#frmqform-answer"+response.hint);						elms.parent().addClass("invalid");						// $("#frmqform-answer"+response.hint).attr('disabled', true);					}					else 					{					}										// fix pre nadbytocny request sposobeny zaokruhlenim nadol pre rychlejsie vykonanie hintu					if ( !response.hints ) {						clearInterval(kQuiz.hint_int);					}				}				return true;			}, // End of bindHint			getHint: function () {				$.getJSON(kQuiz.hint_url + kQuiz.question.id, null, kQuiz.bindHint);				return true;			}, // End of getHint			initAnswer: function (response) {				if (response.answer) {					if ( kQuiz.question.type == "multi" )					{						// TODO nejaky lepsi selektor na moznosti, ( podla classy? )						var answers = response.answer;						var elms = $("#qform :input:checkbox");						var correct_elms = [];												for ( var i = 0; i < answers.length; i++ ) {							correct_elms[correct_elms.length] = $("#frmqform-answer"+answers[i]).get(0);							// correct_elms[correct_elms.length].parent().addClass("correct");						}						for ( var i = 0; i < elms.length; i++ ) {							if ( correct_elms.indexOf(elms[i]) != -1 ) {								$(elms[i]).parent().addClass("correct");								if ( elms[i].checked ) {									$(elms[i]).parent().addClass("valid-checked");								}							} else {								$(elms[i]).parent().addClass("invalid");								if ( elms[i].checked ) {									$(elms[i]).parent().addClass("invalid-checked");								}							}						}																	}					else 					{					}				}				setTimeout(function() { $.getJSON(kQuiz.url, null, kQuiz.initData); } , 5000);								return true;			}, // End of initAnswer			getAnswer: function () {				$.getJSON(kQuiz.answer_url + kQuiz.question.id, null, kQuiz.initAnswer);				return true;			}, // End of getAnswer						initQuestion: function (question) {				kQuiz.question = question;				kQuiz.form_submitted = false;				$("#question-timer").html(kQuiz.question.time);				kQuiz.qcdi = setInterval(kQuiz.questionCountDown, 1000);				// 2 sekundy pred koncom submitnem a pockam na odpovede ( pre istotu aby bola odpoved uzivatela odoslana) 				// kQuiz.question_update_timeout = setTimeout(kQuiz.questionUpdate, (kQuiz.question.time * 1000) - 1000);				if ( kQuiz.question.hints != 0) {					var hp = Math.round(( kQuiz.question.time / 100 ) * ( 100 / ( kQuiz.question.hints + 1 ) ));					kQuiz.hint_int = setInterval(kQuiz.getHint, hp * 1000);				}				kQuiz.question_update_timeout = setTimeout(kQuiz.questionUpdate, (kQuiz.question.time * 1000) - 2000);			},				init: function(config) {				this.url = ( config.url ) ? config.url : null;				this.hint_url = ( config.hint_url ) ? config.hint_url : null;				this.answer_url = ( config.answer_url ) ? config.answer_url : null;				this.initData(config);			}		};			kQuiz.init(config);		return kQuiz;	};$(function () {		if ( !msieold ) 	{		if ( typeof(quiz_config) != "undefined" ) {			var myQuiz = new kQuiz(quiz_config);		}			// kazdych 30 sekund updatnem pocet prihlasenych ludi		if ($("#logged-users")) {			var refreshId = setInterval(function()			{				$.get('/?do=loggedUsers');			}, 60000);		}	}	else 	{		alert("Your browser does not support the full functionality of this application.");	}});