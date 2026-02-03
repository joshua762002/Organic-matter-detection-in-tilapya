const loginForm = document.getElementById('loginForm');
const errorMsg = document.getElementById('error-msg');

loginForm.addEventListener('submit', function(e){
  e.preventDefault();

  const username = document.getElementById('username').value;
  const password = document.getElementById('password').value;

 
  const adminUser = "admin";
  const adminPass = "isda123";

  if(username === adminUser && password === adminPass){
    
    window.location.href = "index.html";
  } else {
    errorMsg.textContent = "Invalid username or password!";
  }
});
