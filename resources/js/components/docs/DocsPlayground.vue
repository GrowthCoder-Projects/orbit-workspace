<script setup lang="ts">
import { Code2, Play, Copy, Check, KeyRound, Terminal } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Endpoint {
    id: string;
    module: string;
    title: string;
    method: 'GET' | 'POST' | 'PATCH' | 'DELETE';
    path: string;
    description: string;
    params: any[];
    requestBody: any;
    response: any;
}

const props = defineProps<{
    endpoint: Endpoint;
    baseUrl: string;
}>();

const activeTab = ref<'curl' | 'python' | 'js' | 'php'>('curl');
const apiToken = ref('');
const copied = ref(false);
const isTesting = ref(false);
const liveResponse = ref<{
    status: number | null;
    statusText: string;
    data: any;
} | null>(null);

onMounted(() => {
    const saved = localStorage.getItem('growthcoder_api_token');
    if (saved) {
        apiToken.value = saved;
    }
});

watch(apiToken, (val) => {
    localStorage.setItem('growthcoder_api_token', val);
});

// Clear response when endpoint changes
watch(() => props.endpoint.id, () => {
    liveResponse.value = null;
});

const fullUrl = computed(() => {
    return `${props.baseUrl}${props.endpoint.path}`;
});

const tokenHeader = computed(() => {
    return apiToken.value ? `1|${apiToken.value}` : 'YOUR_API_TOKEN';
});

const curlSnippet = computed(() => {
    let code = `curl -X ${props.endpoint.method} ${fullUrl.value} \\\n  -H "Authorization: Bearer ${tokenHeader.value}" \\\n  -H "Content-Type: application/json" \\\n  -H "Accept: application/json"`;
    if (props.endpoint.requestBody) {
        code += ` \\\n  -d '${JSON.stringify(props.endpoint.requestBody)}'`;
    }
    return code;
});

const pythonSnippet = computed(() => {
    let code = `import requests\n\nheaders = {\n  "Authorization": "Bearer ${tokenHeader.value}",\n  "Content-Type": "application/json",\n  "Accept": "application/json"\n}\n\n`;
    if (props.endpoint.requestBody) {
        code += `payload = ${JSON.stringify(props.endpoint.requestBody, null, 2)}\n\n`;
        code += `res = requests.${props.endpoint.method.toLowerCase()}("${fullUrl.value}", json=payload, headers=headers)\n`;
    } else {
        code += `res = requests.${props.endpoint.method.toLowerCase()}("${fullUrl.value}", headers=headers)\n`;
    }
    code += `print(res.json())`;
    return code;
});

const jsSnippet = computed(() => {
    let code = `const res = await fetch("${fullUrl.value}", {\n  method: "${props.endpoint.method}",\n  headers: {\n    "Authorization": "Bearer ${tokenHeader.value}",\n    "Content-Type": "application/json",\n    "Accept": "application/json"\n  }`;
    if (props.endpoint.requestBody) {
        code += `,\n  body: JSON.stringify(${JSON.stringify(props.endpoint.requestBody, null, 4)})\n`;
    } else {
        code += `\n`;
    }
    code += `});\nconst data = await res.json();\nconsole.log(data);`;
    return code;
});

const phpSnippet = computed(() => {
    let code = `use Illuminate\\Support\\Facades\\Http;\n\n$response = Http::withToken('${tokenHeader.value}')\n  ->acceptJson()`;
    if (props.endpoint.requestBody) {
        code += `\n  ->${props.endpoint.method.toLowerCase()}('${fullUrl.value}', ${JSON.stringify(props.endpoint.requestBody, null, 4)});\n\n`;
    } else {
        code += `\n  ->${props.endpoint.method.toLowerCase()}('${fullUrl.value}');\n\n`;
    }
    code += `$data = $response->json();`;
    return code;
});

const currentSnippet = computed(() => {
    switch (activeTab.value) {
        case 'curl':
            return curlSnippet.value;
        case 'python':
            return pythonSnippet.value;
        case 'js':
            return jsSnippet.value;
        case 'php':
            return phpSnippet.value;
    }
});

const copyCode = () => {
    navigator.clipboard.writeText(currentSnippet.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const runTryItOut = async () => {
    isTesting.value = true;
    liveResponse.value = null;

    try {
        const headers: Record<string, string> = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };

        if (apiToken.value.trim()) {
            headers['Authorization'] = `Bearer ${apiToken.value.trim()}`;
        }

        const options: RequestInit = {
            method: props.endpoint.method,
            headers,
        };

        if (props.endpoint.requestBody && props.endpoint.method !== 'GET') {
            options.body = JSON.stringify(props.endpoint.requestBody);
        }

        const res = await fetch(props.endpoint.path, options);
        let data = {};
        try {
            data = await res.json();
        } catch {
            data = { raw: await res.text() };
        }

        liveResponse.value = {
            status: res.status,
            statusText: res.statusText,
            data,
        };
    } catch (e: any) {
        liveResponse.value = {
            status: 500,
            statusText: 'Network Error',
            data: { error: e.message },
        };
    } finally {
        isTesting.value = false;
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- API Token Bar -->
        <div class="rounded-xl border bg-card p-4 space-y-3 shadow-sm">
            <div class="flex items-center space-x-2 text-xs font-semibold text-foreground">
                <KeyRound class="h-4 w-4 text-primary" />
                <span>API Bearer Token</span>
            </div>
            <Input
                v-model="apiToken"
                type="password"
                placeholder="Paste your API token here..."
                class="font-mono text-xs"
                autocomplete="off"
            />
            <p class="text-[11px] text-muted-foreground">
                Token saved locally in browser for live testing.
            </p>
        </div>

        <!-- Code Snippet Container -->
        <div class="rounded-xl border bg-slate-950 overflow-hidden shadow-md space-y-0">
            <!-- Tabs Header -->
            <div class="flex items-center justify-between px-3 py-2 bg-slate-900 border-b border-slate-800 text-xs font-mono">
                <div class="flex items-center space-x-1">
                    <button
                        type="button"
                        :class="[
                            'px-2.5 py-1 rounded transition-colors',
                            activeTab === 'curl' ? 'bg-primary text-primary-foreground font-semibold' : 'text-slate-400 hover:text-slate-200'
                        ]"
                        @click="activeTab = 'curl'"
                    >
                        cURL
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-2.5 py-1 rounded transition-colors',
                            activeTab === 'python' ? 'bg-primary text-primary-foreground font-semibold' : 'text-slate-400 hover:text-slate-200'
                        ]"
                        @click="activeTab = 'python'"
                    >
                        Python
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-2.5 py-1 rounded transition-colors',
                            activeTab === 'js' ? 'bg-primary text-primary-foreground font-semibold' : 'text-slate-400 hover:text-slate-200'
                        ]"
                        @click="activeTab = 'js'"
                    >
                        JavaScript
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-2.5 py-1 rounded transition-colors',
                            activeTab === 'php' ? 'bg-primary text-primary-foreground font-semibold' : 'text-slate-400 hover:text-slate-200'
                        ]"
                        @click="activeTab = 'php'"
                    >
                        PHP
                    </button>
                </div>

                <Button type="button" variant="ghost" size="sm" class="h-7 text-slate-400 hover:text-slate-100 hover:bg-slate-800" @click="copyCode">
                    <Check v-if="copied" class="h-3.5 w-3.5 text-emerald-400" />
                    <Copy v-else class="h-3.5 w-3.5" />
                </Button>
            </div>

            <!-- Code Body -->
            <pre class="p-4 font-mono text-xs text-slate-200 overflow-x-auto leading-relaxed"><code>{{ currentSnippet }}</code></pre>

            <!-- Try It Out Bar -->
            <div class="p-3 bg-slate-900 border-t border-slate-800 flex items-center justify-between">
                <span class="text-xs font-mono text-slate-400 flex items-center gap-1.5">
                    <Terminal class="h-3.5 w-3.5" />
                    Interactive Test
                </span>

                <Button
                    type="button"
                    size="sm"
                    class="h-8 text-xs font-medium"
                    :disabled="isTesting"
                    @click="runTryItOut"
                >
                    <span v-if="isTesting" class="mr-1.5 h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
                    <Play v-else class="mr-1.5 h-3.5 w-3.5 fill-current" />
                    Try It Out
                </Button>
            </div>
        </div>

        <!-- Live Response Result Panel -->
        <div v-if="liveResponse" class="rounded-xl border bg-card p-4 space-y-3 shadow-sm animate-in fade-in slide-in-from-top-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-foreground">Live Response Result</span>
                <span
                    :class="[
                        'px-2 py-0.5 rounded text-xs font-mono font-bold border',
                        liveResponse.status && liveResponse.status < 300
                            ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30'
                            : 'bg-rose-500/10 text-rose-600 border-rose-500/30'
                    ]"
                >
                    {{ liveResponse.status }} {{ liveResponse.statusText }}
                </span>
            </div>

            <pre class="rounded-lg border bg-slate-950 p-3 font-mono text-xs text-slate-200 overflow-x-auto max-h-64"><code>{{ JSON.stringify(liveResponse.data, null, 2) }}</code></pre>
        </div>
    </div>
</template>
