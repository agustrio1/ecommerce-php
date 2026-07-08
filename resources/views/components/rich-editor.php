<?php
$editorId   = $id ?? 'rich_editor_' . uniqid();
$inputName  = $name ?? 'content';
$inputValue = $value ?? '';
?>

<div
    x-data="richEditor('<?= $editorId ?>')"
    x-init="init()"
    class="border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-orange-500 focus-within:border-orange-500 bg-white">

    <!-- Toolbar -->
    <div class="flex flex-wrap gap-0.5 p-2 bg-gray-50 border-b border-gray-200">

        <div class="flex gap-0.5 mr-1">
            <button type="button"
                @mousedown.prevent="exec('bold')"
                @touchend.prevent="exec('bold')"
                :class="active.bold ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100'"
                class="p-1.5 rounded text-sm font-bold w-8 h-8 flex items-center justify-center transition select-none">B</button>

            <button type="button"
                @mousedown.prevent="exec('italic')"
                @touchend.prevent="exec('italic')"
                :class="active.italic ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100'"
                class="p-1.5 rounded text-sm italic w-8 h-8 flex items-center justify-center transition select-none">I</button>

            <button type="button"
                @mousedown.prevent="exec('underline')"
                @touchend.prevent="exec('underline')"
                :class="active.underline ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100'"
                class="p-1.5 rounded text-sm underline w-8 h-8 flex items-center justify-center transition select-none">U</button>

            <button type="button"
                @mousedown.prevent="exec('strikeThrough')"
                @touchend.prevent="exec('strikeThrough')"
                :class="active.strikeThrough ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100'"
                class="p-1.5 rounded text-sm line-through w-8 h-8 flex items-center justify-center transition select-none">S</button>
        </div>

        <div class="w-px bg-gray-200 mx-0.5 self-stretch"></div>

        <div class="flex gap-0.5 mr-1">
            <button type="button"
                @mousedown.prevent="exec('formatBlock', 'H1')"
                @touchend.prevent="exec('formatBlock', 'H1')"
                class="px-2 h-8 rounded text-xs font-bold text-gray-600 hover:bg-gray-100 transition select-none">H1</button>

            <button type="button"
                @mousedown.prevent="exec('formatBlock', 'H2')"
                @touchend.prevent="exec('formatBlock', 'H2')"
                class="px-2 h-8 rounded text-xs font-bold text-gray-600 hover:bg-gray-100 transition select-none">H2</button>

            <button type="button"
                @mousedown.prevent="exec('formatBlock', 'H3')"
                @touchend.prevent="exec('formatBlock', 'H3')"
                class="px-2 h-8 rounded text-xs font-bold text-gray-600 hover:bg-gray-100 transition select-none">H3</button>

            <button type="button"
                @mousedown.prevent="exec('formatBlock', 'P')"
                @touchend.prevent="exec('formatBlock', 'P')"
                class="px-2 h-8 rounded text-xs text-gray-600 hover:bg-gray-100 transition select-none">P</button>
        </div>

        <div class="w-px bg-gray-200 mx-0.5 self-stretch"></div>

        <div class="flex gap-0.5 mr-1">
            <button type="button"
                @mousedown.prevent="exec('insertUnorderedList')"
                @touchend.prevent="exec('insertUnorderedList')"
                :class="active.insertUnorderedList ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100'"
                class="p-1.5 rounded w-8 h-8 flex items-center justify-center transition select-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                </svg>
            </button>

            <button type="button"
                @mousedown.prevent="exec('insertOrderedList')"
                @touchend.prevent="exec('insertOrderedList')"
                :class="active.insertOrderedList ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100'"
                class="p-1.5 rounded w-8 h-8 flex items-center justify-center transition select-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h10M7 16h10M3 8h.01M3 12h.01M3 16h.01"/>
                </svg>
            </button>

            <button type="button"
                @mousedown.prevent="exec('formatBlock', 'BLOCKQUOTE')"
                @touchend.prevent="exec('formatBlock', 'BLOCKQUOTE')"
                class="p-1.5 rounded w-8 h-8 text-gray-600 hover:bg-gray-100 flex items-center justify-center transition select-none">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                </svg>
            </button>
        </div>

        <div class="w-px bg-gray-200 mx-0.5 self-stretch"></div>

        <div class="flex gap-0.5 mr-1">
            <button type="button"
                @mousedown.prevent="insertLink()"
                @touchend.prevent="insertLink()"
                class="p-1.5 rounded w-8 h-8 text-gray-600 hover:bg-gray-100 flex items-center justify-center transition select-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </button>

            <button type="button"
                @mousedown.prevent="exec('unlink')"
                @touchend.prevent="exec('unlink')"
                class="p-1.5 rounded w-8 h-8 text-gray-600 hover:bg-gray-100 flex items-center justify-center transition select-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m-3.536-3.536a4 4 0 01-5.656-5.656M6.343 6.343a9 9 0 000 12.728"/>
                </svg>
            </button>
        </div>

        <div class="w-px bg-gray-200 mx-0.5 self-stretch"></div>

        <button type="button"
            @mousedown.prevent="exec('removeFormat')"
            @touchend.prevent="exec('removeFormat')"
            class="p-1.5 rounded w-8 h-8 text-gray-400 hover:bg-gray-100 hover:text-red-500 flex items-center justify-center transition select-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Area edit -->
    <div
        :id="editorId"
        contenteditable="true"
        @input="syncContent()"
        @keyup="updateActive()"
        @mouseup="updateActive()"
        @touchend="updateActive()"
        @blur="saveSelection()"
        class="min-h-48 p-4 text-sm text-gray-800 focus:outline-none leading-relaxed
               [&_h1]:text-2xl [&_h1]:font-bold [&_h1]:mb-2 [&_h1]:mt-3
               [&_h2]:text-xl [&_h2]:font-bold [&_h2]:mb-2 [&_h2]:mt-3
               [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:mb-1 [&_h3]:mt-2
               [&_p]:mb-2
               [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mb-2
               [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mb-2
               [&_blockquote]:border-l-4 [&_blockquote]:border-orange-400 [&_blockquote]:pl-3 [&_blockquote]:italic [&_blockquote]:text-gray-600 [&_blockquote]:my-2
               [&_a]:text-orange-600 [&_a]:underline">
    </div>

    <!-- Hidden input submit -->
    <input type="hidden" name="<?= e($inputName) ?>" id="<?= $editorId ?>_hidden">
</div>

<script>
(function() {
    function initEditor_<?= $editorId ?>() {
        var el = document.getElementById('<?= $editorId ?>');
        var hiddenInput = document.getElementById('<?= $editorId ?>_hidden');

        if (!el) return;

        var encoded = '<?= base64_encode($inputValue) ?>';
        var decoded = encoded ? atob(encoded) : '';

        el.innerHTML = decoded;
        if (hiddenInput) hiddenInput.value = decoded;

        el.addEventListener('input', function() {
            if (hiddenInput) hiddenInput.value = el.innerHTML;
        });

        var form = el.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                if (hiddenInput) hiddenInput.value = el.innerHTML;
            }, true);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditor_<?= $editorId ?>);
    } else {
        setTimeout(initEditor_<?= $editorId ?>, 50);
    }
})();

function richEditor(editorId) {
    return {
        editorId,
        content: '',
        savedRange: null,
        active: {
            bold: false,
            italic: false,
            underline: false,
            strikeThrough: false,
            insertUnorderedList: false,
            insertOrderedList: false,
        },

        init() {
            var el = document.getElementById(this.editorId);
            if (el) this.content = el.innerHTML;
        },

        saveSelection() {
            var sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                this.savedRange = sel.getRangeAt(0).cloneRange();
            }
        },

        exec(command, value) {
            value = value || null;
            var el = document.getElementById(this.editorId);
            var hiddenInput = document.getElementById(this.editorId + '_hidden');
            if (!el) return;

            if (this.savedRange) {
                el.focus();
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(this.savedRange);
            } else {
                el.focus();
            }

            document.execCommand(command, false, value);

            this.content = el.innerHTML;
            if (hiddenInput) hiddenInput.value = this.content;

            this.updateActive();

            var sel2 = window.getSelection();
            if (sel2 && sel2.rangeCount > 0) {
                this.savedRange = sel2.getRangeAt(0).cloneRange();
            }
        },

        syncContent() {
            var el = document.getElementById(this.editorId);
            var hiddenInput = document.getElementById(this.editorId + '_hidden');
            if (el) {
                this.content = el.innerHTML;
                if (hiddenInput) hiddenInput.value = this.content;
            }
        },

        updateActive() {
            // Reset semua ke false dulu sebelum query ulang
            // Fix bug: dua tombol aktif sekaligus karena state lama tidak di-reset
            var self = this;
            var cmds = ['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList'];

            cmds.forEach(function(cmd) {
                self.active[cmd] = false;
            });

            // Simpan selection dulu
            self.saveSelection();

            // Query state tiap command
            cmds.forEach(function(cmd) {
                try {
                    self.active[cmd] = document.queryCommandState(cmd);
                } catch(e) {
                    self.active[cmd] = false;
                }
            });
        },

        insertLink() {
            var url = prompt('Masukkan URL:');
            if (url) this.exec('createLink', url);
        }
    }
}
</script>