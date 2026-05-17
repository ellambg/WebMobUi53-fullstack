<script setup>
import { ref, computed, onMounted } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';
import { usePolling } from '@/composables/usePolling';
import PollChart from '@/components/PollChart.vue';

const props = defineProps({
  token: { type: String, required: true },
  loginUrl: { type: String, default: null },
});

const { fetchApi } = useFetchApi();

const poll = ref(null);
const error = ref(null);
const loading = ref(true);
const selectedOptions = ref([]);
const voteError = ref(null);
const voteSuccess = ref(false);
const isAuthenticated = ref(false);

async function loadPoll() {
  try {
    const data = await fetchApi({ url: 'polls/' + props.token });
    poll.value = data;
  } catch (err) {
    error.value = 'Sondage introuvable.';
  } finally {
    loading.value = false;
  }
}

async function loadResults() {
  try {
    const data = await fetchApi({ url: 'polls/' + props.token + '/results' });
    if (data?.poll?.options) {
      poll.value.options = data.poll.options;
    }
  } catch {}
}

async function checkAuth() {
  try {
    const response = await fetch('/api/user', {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    });
    isAuthenticated.value = response.ok;
  } catch {
    isAuthenticated.value = false;
  }
}

const isExpired = computed(() => {
  if (!poll.value?.ends_at) return false;
  return new Date(poll.value.ends_at) < new Date();
});

const totalVotes = computed(() => {
  if (!poll.value?.options) return 0;
  return poll.value.options.reduce((sum, o) => sum + (o.votes_count ?? 0), 0);
});

function toggleOption(id) {
  if (poll.value?.allow_multiple_choices) {
    if (selectedOptions.value.includes(id)) {
      selectedOptions.value = selectedOptions.value.filter(o => o !== id);
    } else {
      selectedOptions.value.push(id);
    }
  } else {
    selectedOptions.value = [id];
  }
}

async function submitVote() {
  voteError.value = null;
  try {
    await fetchApi({
      url: 'polls/' + props.token + '/vote',
      method: 'POST',
      data: { option_ids: selectedOptions.value },
    });
    voteSuccess.value = true;
    await loadResults();
  } catch (err) {
    voteError.value = err.data?.message ?? 'Erreur lors du vote.';
  }
}

onMounted(async () => {
  await checkAuth();
  await loadPoll();
});

usePolling(loadResults, 5000);
</script>

<template>
  <div class="container">
    <div v-if="loading">Chargement...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else-if="poll">
      <h1>{{ poll.title || poll.question }}</h1>
      <p v-if="poll.title" class="question">{{ poll.question }}</p>

      <div v-if="poll.is_draft" class="info">Ce sondage n'est pas encore lancé.</div>
      <div v-else-if="isExpired" class="expired">Ce sondage est terminé.</div>

      <div v-if="voteSuccess" class="success">✅ Vote enregistré !</div>

      <div class="options">
        <div
          v-for="option in poll.options"
          :key="option.id"
          class="option"
          :class="{ selected: selectedOptions.includes(option.id) }"
          @click="!voteSuccess && !isExpired && !poll.is_draft && isAuthenticated ? toggleOption(option.id) : null"
        >
          <span class="option-label">{{ option.label }}</span>
          <span class="option-votes">{{ option.votes_count ?? 0 }} vote(s)</span>
          <div class="option-bar">
            <div
              class="option-bar-fill"
              :style="{ width: totalVotes > 0 ? (option.votes_count / totalVotes * 100) + '%' : '0%' }"
            ></div>
          </div>
        </div>
      </div>

      <PollChart v-if="totalVotes > 0" :options="poll.options" :totalVotes="totalVotes" />

      <div v-if="!isAuthenticated" class="info">
        <a :href="loginUrl">Connectez-vous</a> pour voter.
      </div>

      <div v-else-if="!voteSuccess && !isExpired && !poll.is_draft">
        <div v-if="voteError" class="error">{{ voteError }}</div>
        <button
          @click="submitVote"
          :disabled="selectedOptions.length === 0"
          class="btn-vote"
        >
          Voter
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.container { max-width: 600px; margin: 2rem auto; padding: 1rem; }
h1 { font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem; }
.question { color: #666; margin-bottom: 1rem; }
.options { display: flex; flex-direction: column; gap: 0.75rem; margin: 1.5rem 0; }
.option {
  border: 2px solid #ddd;
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}
.option.selected { border-color: #3490dc; background: #ebf5ff; }
.option-label { font-weight: 500; display: block; }
.option-votes { font-size: 0.85rem; color: #666; }
.option-bar { height: 4px; background: #eee; margin-top: 0.5rem; border-radius: 2px; }
.option-bar-fill { height: 100%; background: #3490dc; border-radius: 2px; transition: width 0.3s; }
.btn-vote {
  background: #3490dc; color: white; border: none;
  border-radius: 0.25rem; padding: 0.6rem 1.5rem;
  font-size: 1rem; cursor: pointer;
}
.btn-vote:disabled { background: #ccc; cursor: not-allowed; }
.error { background: #fee; color: #e3342f; padding: 0.5rem; border-radius: 0.25rem; margin-bottom: 1rem; }
.success { background: #f0fff4; color: #2d6a4f; padding: 0.5rem; border-radius: 0.25rem; margin-bottom: 1rem; }
.info { background: #fff3cd; color: #856404; padding: 0.5rem; border-radius: 0.25rem; margin-bottom: 1rem; }
.expired { background: #f8d7da; color: #721c24; padding: 0.5rem; border-radius: 0.25rem; margin-bottom: 1rem; }
</style>
