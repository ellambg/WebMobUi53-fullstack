<script setup>
import { ref, computed, onMounted } from "vue";
import { useFetchApi } from "@/composables/useFetchApi";
import { usePolling } from "@/composables/usePolling";
import PollChart from "@/components/PollChart.vue";

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
const alreadyVoted = ref(false);
const isAuthenticated = ref(false);

async function loadPoll() {
    try {
        const data = await fetchApi({ url: "polls/" + props.token });
        poll.value = data;
        if (data.has_voted) alreadyVoted.value = true;
    } catch (err) {
        error.value = "Sondage introuvable.";
    } finally {
        loading.value = false;
    }
}

async function loadResults() {
    try {
        const data = await fetchApi({
            url: "polls/" + props.token + "/results",
        });
        if (data?.poll?.options) {
            poll.value.options = data.poll.options;
        }
    } catch {}
}

async function checkAuth() {
    try {
        const response = await fetch("/api/user", {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
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
            selectedOptions.value = selectedOptions.value.filter(
                (o) => o !== id,
            );
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
            url: "polls/" + props.token + "/vote",
            method: "POST",
            data: { option_ids: selectedOptions.value },
        });
        voteSuccess.value = true;
        await loadResults();
        if (poll.value.allow_vote_change) {
            setTimeout(() => {
                voteSuccess.value = false;
                selectedOptions.value = [];
                alreadyVoted.value = true;
            }, 2000);
        }
    } catch (err) {
        const msg = err.data?.message;
        if (msg === "Already voted.") {
            alreadyVoted.value = true;
            voteError.value = "Vous avez déjà voté.";
        } else if (msg === "Poll has ended.") {
            voteError.value = "Ce sondage est terminé.";
        } else if (msg === "Poll not started.") {
            voteError.value = "Ce sondage n'est pas encore lancé.";
        } else {
            voteError.value = msg ?? "Erreur lors du vote.";
        }
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

            <div v-if="poll.is_draft" class="info">
                Ce sondage n'est pas encore lancé.
            </div>
            <div v-else-if="isExpired" class="expired">
                Ce sondage est terminé.
            </div>

            <div v-if="voteSuccess" class="success">✅ Vote enregistré !</div>

            <div class="options">
                <div
                    v-for="option in poll.options"
                    :key="option.id"
                    class="option"
                    :class="{
                        selected: selectedOptions.includes(option.id),
                        voted: alreadyVoted && !poll.allow_vote_change,
                    }"
                    @click="
                        !voteSuccess &&
                        !isExpired &&
                        !poll.is_draft &&
                        isAuthenticated &&
                        (!alreadyVoted || poll.allow_vote_change)
                            ? toggleOption(option.id)
                            : null
                    "
                >
                    <span class="option-label">{{ option.label }}</span>
                    <span class="option-votes"
                        >{{ option.votes_count ?? 0 }} vote(s)</span
                    >
                    <div class="option-bar">
                        <div
                            class="option-bar-fill"
                            :style="{
                                width:
                                    totalVotes > 0
                                        ? (option.votes_count / totalVotes) *
                                              100 +
                                          '%'
                                        : '0%',
                            }"
                        ></div>
                    </div>
                </div>
            </div>

            <PollChart
                v-if="totalVotes > 0"
                :options="poll.options"
                :totalVotes="totalVotes"
            />

            <div v-if="!isAuthenticated" class="info">
                <a :href="loginUrl">Connectez-vous</a> pour voter.
            </div>

            <div
                v-else-if="alreadyVoted && !poll.allow_vote_change"
                class="info"
            >
                Vous avez déjà voté à ce sondage.
            </div>

            <div
                v-else-if="
                    !voteSuccess &&
                    !isExpired &&
                    !poll.is_draft &&
                    (!alreadyVoted || poll.allow_vote_change)
                "
            ></div>
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
</template>

<style scoped>
.container {
    max-width: 650px;
    margin: 2rem auto;
    padding: 1.5rem;
    font-family: system-ui, sans-serif;
}
h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #1a202c;
}
.question {
    color: #718096;
    margin-bottom: 1.5rem;
    font-size: 1rem;
}
.options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin: 1.5rem 0;
}
.option {
    border: 2px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}
.option:hover {
    border-color: #3490dc;
    background: #f7faff;
}
.option.selected {
    border-color: #3490dc;
    background: #ebf5ff;
}
.option-label {
    font-weight: 600;
    display: block;
    color: #2d3748;
    margin-bottom: 0.25rem;
}
.option-votes {
    font-size: 0.85rem;
    color: #718096;
}
.option-bar {
    height: 6px;
    background: #edf2f7;
    margin-top: 0.5rem;
    border-radius: 3px;
    overflow: hidden;
}
.option-bar-fill {
    height: 100%;
    background: #3490dc;
    border-radius: 3px;
    transition: width 0.4s ease;
}
.btn-vote {
    background: #3490dc;
    color: white;
    border: none;
    border-radius: 0.5rem;
    padding: 0.75rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 0.5rem;
    transition: background 0.2s;
}
.btn-vote:hover {
    background: #2779bd;
}
.btn-vote:disabled {
    background: #a0aec0;
    cursor: not-allowed;
}
.error {
    background: #fff5f5;
    color: #c53030;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #feb2b2;
    margin-bottom: 1rem;
}
.success {
    background: #f0fff4;
    color: #276749;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #9ae6b4;
    margin-bottom: 1rem;
    font-weight: 600;
}
.info {
    background: #fffbeb;
    color: #744210;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #f6e05e;
    margin-bottom: 1rem;
}
.info a {
    color: #3490dc;
    font-weight: 600;
}
.expired {
    background: #fff5f5;
    color: #c53030;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #feb2b2;
    margin-bottom: 1rem;
    font-weight: 600;
}
.option.voted {
    cursor: not-allowed;
    opacity: 0.8;
}
.option.voted:hover {
    border-color: #e2e8f0;
    background: white;
}
</style>
