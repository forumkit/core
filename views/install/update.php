<h2>Update Forumkit</h2>

<form method="post">
  <div id="fk-system-messages" style="display:none"></div>

  <div class="FormGroup">
    <div class="FormField">
      <label>Database Password</label>
      <input type="password" name="databasePassword">
    </div>
  </div>

  <div class="FormButtons">
    <button type="submit">Update Forumkit</button>
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
              button.textContent = 'Update Forumkit';
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

