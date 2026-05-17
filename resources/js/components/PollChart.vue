<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  options: { type: Array, required: true },
  totalVotes: { type: Number, default: 0 },
});

const canvas = ref(null);
let chart = null;

async function renderChart() {
  const { Chart, registerables } = await import('chart.js');
  Chart.register(...registerables);

  const labels = props.options.map(o => o.label);
  const data = props.options.map(o => o.votes_count ?? 0);

  if (chart) {
    chart.data.labels = labels;
    chart.data.datasets[0].data = data;
    chart.update();
    return;
  }

  chart = new Chart(canvas.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Votes',
        data,
        backgroundColor: '#3490dc',
        borderRadius: 4,
      }],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 },
        },
      },
    },
  });
}

onMounted(renderChart);
watch(() => props.options, renderChart, { deep: true });
</script>

<template>
  <div class="chart-container">
    <canvas ref="canvas"></canvas>
  </div>
</template>

<style scoped>
.chart-container {
  margin-top: 1.5rem;
  max-width: 500px;
}
</style>
