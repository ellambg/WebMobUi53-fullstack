import { ref } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';

const polls = ref([]);

export function usePollStore() {
  const { fetchApi } = useFetchApi();

  function setPolls(data) {
    polls.value = data;
  }

  async function deletePoll(id) {
    await fetchApi({ url: 'polls/' + id, method: 'DELETE' });
    polls.value = polls.value.filter(p => p.id !== id);
  }

  async function createPoll(data) {
    const result = await fetchApi({ url: 'polls', method: 'POST', data });
    polls.value.unshift(result);
    return result;
  }

  async function updatePoll(id, data) {
    const result = await fetchApi({ url: 'polls/' + id, method: 'PUT', data });
    const index = polls.value.findIndex(p => p.id === id);
    if (index !== -1) polls.value.splice(index, 1, result);
    return result;
  }

  return { polls, setPolls, deletePoll, createPoll, updatePoll };
}
