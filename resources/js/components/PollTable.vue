<script setup>
import { ref } from 'vue';
import { usePollStore } from '@/stores/usePollStore';
import PollForm from './PollForm.vue';

const { polls, deletePoll } = usePollStore();

const showForm = ref(false);
const editingPoll = ref(null);

function openCreate() {
  editingPoll.value = null;
  showForm.value = true;
}

function openEdit(poll) {
  editingPoll.value = poll;
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editingPoll.value = null;
}

async function delPoll(id) {
  if (confirm('Supprimer ce sondage ?')) {
    await deletePoll(id);
  }
}

function shareLink(token) {
  const url = window.location.origin + '/vote/' + token;
  navigator.clipboard.writeText(url);
  alert('Lien copié : ' + url);
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleString('fr-CH');
}
</script>

<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h1>Mes sondages</h1>
      <button @click="openCreate" class="btn-create">+ Nouveau sondage</button>
    </div>

    <PollForm v-if="showForm" :poll="editingPoll" @close="closeForm" />

    <p v-if="polls.length === 0" class="empty">Aucun sondage pour l'instant.</p>

    <div v-else class="poll-list">
      <div v-for="poll in polls" :key="poll.id" class="poll-card">
        <div class="poll-card-header">
          <div>
            <h2>{{ poll.title || poll.question }}</h2>
            <p v-if="poll.title" class="poll-question">{{ poll.question }}</p>
          </div>
          <span :class="['badge', poll.is_draft ? 'badge-draft' : 'badge-active']">
            {{ poll.is_draft ? 'Brouillon' : 'Lancé' }}
          </span>
        </div>

        <div class="poll-meta">
          <span>📅 Début : {{ formatDate(poll.started_at) }}</span>
          <span>⏱ Fin : {{ formatDate(poll.ends_at) }}</span>
          <span>{{ poll.allow_multiple_choices ? '☑ Choix multiples' : '🔘 Choix unique' }}</span>
          <span>{{ poll.results_public ? '🌐 Résultats publics' : '🔒 Résultats privés' }}</span>
        </div>

        <div class="poll-actions">
          <button @click="openEdit(poll)" class="btn-edit">✏️ Modifier</button>
          <button @click="shareLink(poll.secret_token)" class="btn-share">🔗 Copier le lien</button>
          <button @click="delPoll(poll.id)" class="btn-delete">🗑 Supprimer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard { padding: 2rem; max-width: 900px; margin: 0 auto; font-family: system-ui, sans-serif; }
.dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
h1 { font-size: 1.75rem; font-weight: 700; color: #1a202c; }
.btn-create { background: #3490dc; color: white; border: none; border-radius: 0.5rem; padding: 0.6rem 1.25rem; font-size: 1rem; font-weight: 600; cursor: pointer; }
.btn-create:hover { background: #2779bd; }
.empty { color: #718096; font-size: 1rem; }
.poll-list { display: flex; flex-direction: column; gap: 1rem; }
.poll-card { background: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.poll-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
h2 { font-size: 1.1rem; font-weight: 700; color: #2d3748; }
.poll-question { color: #718096; font-size: 0.9rem; margin-top: 0.25rem; }
.badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 600; }
.badge-draft { background: #fef3c7; color: #92400e; }
.badge-active { background: #d1fae5; color: #065f46; }
.poll-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; font-size: 0.85rem; color: #718096; margin-bottom: 1rem; }
.poll-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.btn-edit { background: #f6993f; color: white; border: none; border-radius: 0.4rem; padding: 0.4rem 0.9rem; cursor: pointer; font-size: 0.9rem; }
.btn-share { background: #4a9c6d; color: white; border: none; border-radius: 0.4rem; padding: 0.4rem 0.9rem; cursor: pointer; font-size: 0.9rem; }
.btn-delete { background: #e3342f; color: white; border: none; border-radius: 0.4rem; padding: 0.4rem 0.9rem; cursor: pointer; font-size: 0.9rem; }
</style>
