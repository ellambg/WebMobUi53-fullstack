<script setup>
import { ref } from 'vue';
import { usePollStore } from '@/stores/usePollStore';

const props = defineProps({
  poll: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const { createPoll, updatePoll } = usePollStore();

const question = ref(props.poll?.question ?? '');
const title = ref(props.poll?.title ?? '');
const is_draft = ref(props.poll?.is_draft ?? true);
const allow_multiple_choices = ref(props.poll?.allow_multiple_choices ?? false);
const results_public = ref(props.poll?.results_public ?? false);
const duration = ref(props.poll?.duration ?? null);
const options = ref(props.poll?.options?.map(o => ({ label: o.label })) ?? [{ label: '' }, { label: '' }]);
const allow_vote_change = ref(props.poll?.allow_vote_change ?? false);

const error = ref(null);

function addOption() {
  options.value.push({ label: '' });
}

function removeOption(index) {
  if (options.value.length > 2) {
    options.value.splice(index, 1);
  }
}

async function submit() {
  error.value = null;
  const data = {
    question: question.value,
    title: title.value,
    is_draft: is_draft.value,
    allow_multiple_choices: allow_multiple_choices.value,
    results_public: results_public.value,
    duration: duration.value ? parseInt(duration.value) : null,
    options: options.value,
    allow_vote_change: allow_vote_change.value,
  };

  try {
    if (props.poll) {
      await updatePoll(props.poll.id, data);
    } else {
      await createPoll(data);
    }
    emit('close');
  } catch (err) {
    error.value = err.data?.message ?? 'Une erreur est survenue.';
  }
}
</script>

<template>
  <div class="form-overlay">
    <div class="form-box">
      <h2>{{ poll ? 'Modifier le sondage' : 'Nouveau sondage' }}</h2>

      <div v-if="error" class="error">{{ error }}</div>

      <label>Titre (optionnel)
        <input v-model="title" type="text" placeholder="Titre du sondage" />
      </label>

      <label>Question *
        <input v-model="question" type="text" placeholder="Votre question" required />
      </label>

      <label>Durée en secondes (optionnel)
        <input v-model="duration" type="number" placeholder="Ex: 3600 pour 1h" min="1" />
      </label>

      <div class="checkboxes">
        <label><input v-model="is_draft" type="checkbox" /> Brouillon</label>
        <label><input v-model="allow_multiple_choices" type="checkbox" /> Choix multiples</label>
        <label><input v-model="results_public" type="checkbox" /> Résultats publics</label>
        <label><input v-model="allow_vote_change" type="checkbox" /> Permettre de changer son vote</label>
      </div>

      <h3>Options (min. 2)</h3>
      <div v-for="(option, index) in options" :key="index" class="option-row">
        <input v-model="option.label" type="text" :placeholder="'Option ' + (index + 1)" />
        <button type="button" @click="removeOption(index)" :disabled="options.length <= 2">✕</button>
      </div>
      <button type="button" @click="addOption" class="btn-add">+ Ajouter une option</button>

      <div class="form-actions">
        <button type="button" @click="emit('close')" class="btn-cancel">Annuler</button>
        <button type="button" @click="submit" class="btn-submit">{{ poll ? 'Modifier' : 'Créer' }}</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.form-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}
.form-box {
  background: white;
  padding: 2rem;
  border-radius: 0.5rem;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
h2 { font-size: 1.25rem; font-weight: bold; }
h3 { font-size: 1rem; font-weight: bold; margin-top: 0.5rem; }
label { display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.9rem; }
input[type="text"], input[type="number"] {
  border: 1px solid #ccc;
  border-radius: 0.25rem;
  padding: 0.4rem 0.6rem;
  font-size: 1rem;
}
.checkboxes { display: flex; flex-direction: column; gap: 0.25rem; }
.checkboxes label { flex-direction: row; align-items: center; gap: 0.5rem; }
.option-row { display: flex; gap: 0.5rem; align-items: center; }
.option-row input { flex: 1; }
.option-row button { background: #e3342f; color: white; border: none; border-radius: 0.25rem; padding: 0.25rem 0.5rem; cursor: pointer; }
.option-row button:disabled { background: #ccc; cursor: not-allowed; }
.btn-add { background: #4a9c6d; color: white; border: none; border-radius: 0.25rem; padding: 0.4rem 0.75rem; cursor: pointer; }
.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem; }
.btn-cancel { background: #ccc; border: none; border-radius: 0.25rem; padding: 0.4rem 0.75rem; cursor: pointer; }
.btn-submit { background: #3490dc; color: white; border: none; border-radius: 0.25rem; padding: 0.4rem 0.75rem; cursor: pointer; }
.error { background: #fee; color: #e3342f; padding: 0.5rem; border-radius: 0.25rem; }
</style>
