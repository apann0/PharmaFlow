    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="delete-modal">
      <div class="modal-box glass">
        <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:.5rem;">Confirm Deletion</h3>
        <p style="color:var(--text-secondary);font-size:.875rem;margin-bottom:1.25rem;">
          Are you sure you want to delete <strong id="delete-item-name">this item</strong>? This action cannot be undone.
        </p>
        <form id="delete-form" method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
          <input type="hidden" name="confirm_delete" value="1">
          <div style="display:flex;gap:.5rem;justify-content:flex-end;">
            <button type="button" id="delete-cancel" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-danger">Delete</button>
          </div>
        </form>
      </div>
    </div>

  </main>
</div>

<script src="<?php echo $basePath; ?>assets/js/main.js"></script>
</body>
</html>
