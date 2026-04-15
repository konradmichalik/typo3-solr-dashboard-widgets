/**
 * Module: @konradmichalik/typo3-solr-dashboard-widgets/IndexQueueErrors
 */
document.addEventListener('DOMContentLoaded', () => {
  const button = document.getElementById('solr-reset-errors');
  if (!button) {
    return;
  }

  button.addEventListener('click', async (event) => {
    event.preventDefault();
    const confirmMessage = button.dataset.confirmMessage || 'Are you sure?';

    if (!confirm(confirmMessage)) {
      return;
    }

    const resetUrl = button.dataset.resetUrl;
    try {
      const response = await fetch(resetUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        location.reload();
      }
    } catch (error) {
      console.error('Failed to reset errors:', error);
    }
  });
});
