<template>
    <div>
        <confirmation-modal
            v-if="confirming"
            title="Commit staged changes"
            buttonText="Commit"
            @confirm="doCommit"
            @cancel="cancelCommit"
        >
            <p>Are you sure you want to commit these changes?</p>
            <ul class="m-2 list-inside">
                <li v-for="file in staged" class="list-disc">{{ file.relative_path }}</li>
            </ul>

            <label for="commit_message">Enter a commit message</label>
            <textarea v-model="commit_message" class="w-full border rounded font-mono p-2 h-48" id="commit_message"></textarea>
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
                <pre>{{ status }}</pre>
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

                this.loaded = true;
                this.bare = response.data.bare;
                this.unstaged = response.data.unstaged;
                this.staged = response.data.staged;
                this.meta = response.data.meta;
                this.up_to_date = response.data.up_to_date;
                this.ahead = response.data.ahead;
                this.behind = response.data.behind;
                this.diverged = response.data.diverged;
                this.status = response.data.status;
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
