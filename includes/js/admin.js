const ctx = document.getElementById('cartpulse').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: cartAddLabels,
    datasets: cartAddData
  },
  options: {
    responsive: true
  },
  plugins: {
    tooltip: {
      callbacks: {
        label: function (context) {
          console.log(context); // Log the context for debugging
          // Default dataset label
          let label = context.dataset.label || '';
          if (label) {
            label += ': ';
          }
          label += context.raw; // The value (count)

          // Add custom extra content
          let extraInfo = ' (Some extra info)';

          return label + extraInfo;
        },
        footer: function (context) {
          return 'Extra footer info'; // optional footer
        }
      }
    }
  }
});