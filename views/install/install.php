<h2>Install Forumkit</h2>

<form method="post">
  <div id="error" style="display:none"></div>

  <div class="FormGroup">
    <div class="FormField">
      <label>Forum Title</label>
      <input name="forumTitle">
    </div>
  </div>

  <div class="FormGroup">
    <div class="FormField">
      <label>MySQL Host</label>
      <input name="mysqlHost" value="localhost">
    </div>

    <div class="FormField">
      <label>MySQL Database</label>
      <input name="mysqlDatabase" value="forum">
    </div>

    <div class="FormField">
      <label>MySQL Username</label>
      <input name="mysqlUsername" value="root">
    </div>

    <div class="FormField">
      <label>MySQL Password</label>
      <input type="password" name="mysqlPassword">
    </div>

    <div class="FormField">
      <label>Table Prefix</label>
      <input type="text" name="tablePrefix" value="fk_">
    </div>
  </div>

  <div class="FormGroup">
    <div class="FormField">
      <label>Admin Username</label>
      <input name="adminUsername" value="admin">
    </div>

    <div class="FormField">
      <label>Admin Email</label>
      <input name="adminEmail" value="info@forumkit.cn">
    </div>

    <div class="FormField">
      <label>Admin Password</label>
      <input type="password" name="adminPassword" value="admin">
    </div>

    <div class="FormField">
      <label>Confirm Password</label>
      <input type="password" name="adminPasswordConfirmation" value="admin">
    </div>
  </div>

  <div class="FormButtons">
    <button type="submit">Install Forumkit</button>
  </div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('form input').select();

    document.querySelector('form').addEventListener('submit', function(e) {
      e.preventDefault();

      var button = this.querySelector('button');
      button.textContent = 'Please Wait...';
      button.disabled = true;

      fetch('', {
        method: 'POST',
        body: new FormData(this)
      })
        .then(response => {
          if (response.ok) {
            window.location.reload();
          } else {
            response.text().then(errorMessage => {
              var error = document.querySelector('#error');
              error.style.display = 'block';
              error.textContent = 'Something went wrong:\n\n' + errorMessage;
              button.disabled = false;
              button.textContent = 'Install Forumkit';
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });

      return false;
    });
  });
</script>

