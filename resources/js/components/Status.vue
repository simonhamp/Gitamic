<template>
    <div>
        <confirmation-modal
            v-if="confirming"
            title="Commit staged changes"
            buttonText="Commit"
            @confirm="doCommit"
            @cancel="cancelCommit"
        >
            <p class="text-sm mb-2">
                You have {{ staged.length }} {{ staged.length === 1 ? 'file' : 'files' }} with changes staged for this commit.
                You should write a commit message to describe these changes.
            </p>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="commit_message" class="flex-1">Enter your commit message:</label>
                    <a href="https://chris.beams.io/posts/git-commit/"
                       target="_blank"
                       v-tooltip="'Click to read more about commit messages'"
                       class="bg-white border rounded-full h-4 w-4 p-1.5 shadow-sm text-sm font-bold text-grey-70 inline-flex justify-center items-center"
                    >
                        ?
                    </a>
                </div>
                <textarea v-model="commit_message" class="w-full border rounded font-mono p-2 h-48" id="commit_message"></textarea>
            </div>
        </confirmation-modal>

        <div class="flex mb-3">
            <h1 class="flex-1">{{ __('Gitamic') }}</h1>

            <button class="btn" @click.prevent="getStatus(true)" v-if="! bare">{{ __('Refresh') }}</button>

            <button
                v-if="hasStagedChanges"
                class="ml-2 btn-primary flex items-center"
                @click="confirmCommit">
                <span>{{ __('Commit') }}</span>
            </button>

            <button
                v-if="canPush"
                class="ml-2 flex btn items-center"
                :class="{ 'btn-primary': ! hasStagedChanges }"
                @click="push">
                <span>{{ __('Push') }}</span>
            </button>

            <button
                v-if="canPull"
                class="ml-2 flex btn items-center"
                :class="{ 'btn-primary': ! hasStagedChanges }"
                @click="pull">
                <span>{{ __('Pull') }}</span>
            </button>
        </div>

        <div v-if="! loaded" class="card p-3 text-center">
            <loading-graphic  />
        </div>

        <div v-if="loaded && bare" class="card relative">
            <p class="mb-2">Your repo hasn't been initialized. Please initialize it before using Gitamic:</p>

            <div class="flex justify-center my-4">
                <button class="btn-primary" @click.prevent="initRepo()">{{ __('Initialize repo') }}</button>
            </div>

            <p class="my-2">Alternatively, you can do so manually from your command line:</p>

            <div class="bg-grey-30 py-2 px-3">
                <kbd>
                    git init
                </kbd>
            </div>
        </div>

        <div v-if="loaded && ! bare">
            <div class="my-4">
                <h2 class="mb-2">Status</h2>

                <div class="card">
                    <p>
                        Current branch: <code>{{ current_branch.name }}</code>
                        &rarr;
                        <code>{{ current_branch.tracking }}</code>
                    </p>
                    <p>{{ status }}</p>
                </div>
            </div>

            <div class="my-4">
                <h2 class="mb-2">Staged</h2>
                <gitamic-staged ref="staged" :data="staged"></gitamic-staged>
            </div>

            <div class="my-4">
                <h2 class="mb-2">Unstaged</h2>
                <gitamic-unstaged ref="unstaged" :data="unstaged"></gitamic-unstaged>
            </div>

            <div class="flex justify-center text-center mt-4">
                <div class="bg-white rounded-full px-3 py-1 shadow-sm text-sm text-grey-70">
                    Something not working?
                    <a href="https://github.com/simonhamp/Gitamic/issues/new/choose" target="_blank" class="text-blue hover:text-blue-dark">
                        Get help
                    </a>
                </div>
            </div>

            <div class="my-4 text-sm text-center text-grey-60 tracking-wide" style="font-variant: all-small-caps">
                Gitamic &copy; <a href="https://simonhamp.me/" target="_blank">Simon Hamp</a>
            </div>
        </div>
    </div>
</template>

<script>
    export default {

        props: [
            'data'
        ],

        data() {
            return {
                bare: false,
                loaded: false,
                confirming: false,
                unstaged: [],
                staged: [],
                meta: {},
                commit_message: '',
                up_to_date: true,
                ahead: false,
                behind: false,
                diverged: false,
                status: '',
                current_branch: null,
            }
        },

        computed: {
            hasStagedChanges() {
                return this.staged.length > 0;
            },

            canPull() {
                return this.behind || this.diverged;
            },

            canPush() {
                return this.ahead && ! this.diverged;
            },
        },

        created() {
            for (const [key, value] of Object.entries(this.$props.data)) {
                this[key] = value;
            }

            if (! this.loaded) {
                this.getStatus();
            }
        },

        methods: {
            async getStatus(withLoader) {
                if (withLoader) {
                    this.loaded = false;
                }

                let response = await this.$axios.get(cp_url(`gitamic/api/status`));

                for (const [key, value] of Object.entries(response.data)) {
                    this[key] = value;
                }

                this.loaded = true;
            },

            confirmCommit() {
                this.confirming = true;
            },

            cancelCommit() {
                this.confirming = false;
                this.commit_message = '';
            },

            async doCommit() {
                let response = await this.$axios.post(cp_url(`gitamic/api/commit`), {
                    commit_message: this.commit_message
                });
                await this.getStatus();
                this.confirming = false;
                this.commit_message = '';
            },

            async push() {
                this.loaded = false;
                await this.$axios.post(cp_url(`gitamic/api/push`));

                await this.getStatus();
                this.loaded = true;
            },

            async pull() {
                this.loaded = false;
                await this.$axios.post(cp_url(`gitamic/api/pull`));

                await this.getStatus();
                this.loaded = true;
            },

            async initRepo() {
                this.loaded = false;
                let response = await this.$axios.post(cp_url(`gitamic/api/init`));

                if (response.status === 200) {
                    this.$toast.success('Repo initialized. Loading status...');
                    await this.getStatus();
                } else {
                    this.$toast.error('Failed to initialize. Check your logs. Try initializing manually');
                }

                this.loaded = true;
            }
        }
    }
</script>
