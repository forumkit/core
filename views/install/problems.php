<h2>Hold Up!</h2>

<div class="Problems">
  <?php foreach ($problems as $problem) { ?>
    <div class="Problem">
      <h3 class="Problem-message"><?php echo $problem['message']; ?></h3>
      <?php if (! empty($problem['detail'])) { ?>
        <p class="Problem-detail"><?php echo $problem['detail']; ?></p>
      <?php } ?>
    </div>
  <?php } ?>
</div>
