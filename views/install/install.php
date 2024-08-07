<form method="post">
  <div id="fk-system-messages" style="display:none"></div>

  <div class="FormGroup">
    <div class="FormField">
      <label>Site Title</label>
      <input name="siteTitle" value="Forumkit">
    </div>
  </div>

  <div class="FormGroup">
    <div class="FormField">
      <label>MySQL Host</label>
      <input name="mysqlHost" value="localhost">
    </div>

    <div class="FormField">
      <label>MySQL Database</label>
      <input name="mysqlDatabase">
    </div>

    <div class="FormField">
      <label>MySQL Username</label>
      <input name="mysqlUsername">
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
      <input name="adminUsername">
    </div>

    <div class="FormField">
      <label>Admin Email</label>
      <input name="adminEmail">
    </div>

    <div class="FormField">
      <label>Admin Password</label>
      <input type="password" name="adminPassword">
    </div>

    <div class="FormField">
      <label>Confirm Password</label>
      <input type="password" name="adminPasswordConfirmation">
    </div>
  </div>

  <div class="FormButtons">
    <button type="submit">Install</button>
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
              var error = document.querySelector('#fk-system-messages');
              error.style.display = 'block';
              error.textContent = '出了点问题：\n\n' + errorMessage;
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

