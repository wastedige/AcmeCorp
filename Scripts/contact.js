// JScript source code
$(document).ready(function() {
  console.log('a');
  //global variables
  var form = $("#form");
  var name = form.$("#name"); //textbox u are going to validate
  var email = form.$("#email");
  var subject = form.$("#subject");
  var message = form.$("#message");
  var nameerr = $("nameerr");
  //first validation on form submit
  form.submit(function() {
    // validation begin before submit
    if (validateName()) {
      return true;
    } else {
      return false;
    }
  });
  //declare name validation function
  function validateName() {
    //validation for empty
    if (name.val() == "") {
      name.addClass("error");
      nameerr.text("Names cannot be empty!");
      nameerr.addClass("error");
      return false;
    } else {
      name.removeClass("error");
      nameerr.text("*");
      nameerr.removeClass("error");
    }
    //if it's NOT valid
    if (name.val().length < 2) {
      name.addClass("error");
      nameerr.text("Names with more than 2 letters!");
      nameerr.addClass("error");
      return false;
    }
    //if it's valid
    else {
      name.removeClass("error");
      nameerr.text("*");
      nameerr.removeClass("error");
    }
    // validation only for characters no numbers
    var filter = /^[a-zA-Z]*$/;
    if (filter.test(name.val())) {
      name.removeClass("error");
      nameerr.text("*");
      nameerr.removeClass("error");
      return true;
    } else {
      name.addClass("error");
      nameerr.text("Names cannot have numbers!");
      nameerr.addClass("error");
      return false;
    }
  }
});
