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
</script>

<template>
  <div class="container">
    <div class="header">
      <h1>Mes sondages</h1>
      <button @click="openCreate" class="btn-create">+ Nouveau sondage</button>
    </div>

    <PollForm v-if="showForm" :poll="editingPoll" @close="closeForm" />

    <p v-if="polls.length === 0">Aucun sondage.</p>

    <table v-else class="w-full border-collapse text-left">
      <thead>
        <tr>
          <th class="border px-3 py-2">Actions</th>
          <th class="border px-3 py-2">Titre</th>
          <th class="border px-3 py-2">Question</th>
          <th class="border px-3 py-2">Statut</th>
          <th class="border px-3 py-2">Début</th>
          <th class="border px-3 py-2">Fin</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="poll in polls" :key="poll.id">
          <td class="border px-3 py-2">
            <div class="actions">
              <button @click="openEdit(poll)" class="btn-edit">Modifier</button>
              <button @click="shareLink(poll.secret_token)" class="btn-share">🔗 Lien</button>
              <button @click="delPoll(poll.id)" class="btn-delete">Supprimer</button>
            </div>
          </td>
          <td class="border px-3 py-2">{{ poll.title || '-' }}</td>
          <td class="border px-3 py-2">{{ poll.question }}</td>
          <td class="border px-3 py-2">{{ poll.is_draft ? 'Brouillon' : 'Lancé' }}</td>
          <td class="border px-3 py-2">{{ poll.started_at || '-' }}</td>
          <td class="border px-3 py-2">{{ poll.ends_at || '-' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.container { padding: 1.5rem; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
h1 { font-size: 1.5rem; font-weight: bold; }
.btn-create { background: #3490dc; color: white; border: none; border-radius: 0.25rem; padding: 0.5rem 1rem; cursor: pointer; }
.actions { display: flex; gap: 0.25rem; }
.btn-edit { background: #f6993f; color: white; border: none; border-radius: 0.25rem; padding: 0.25rem 0.5rem; cursor: pointer; }
.btn-share { background: #4a9c6d; color: white; border: none; border-radius: 0.25rem; padding: 0.25rem 0.5rem; cursor: pointer; }
.btn-delete { background: #e3342f; color: white; border: none; border-radius: 0.25rem; padding: 0.25rem 0.5rem; cursor: pointer; }
</style>
