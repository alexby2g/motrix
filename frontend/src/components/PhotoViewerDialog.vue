<template>
  <q-dialog
    :model-value="modelValue"
    maximized
    transition-show="fade"
    transition-hide="fade"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="photo-viewer" @click.self="cerrar">
      <div class="photo-viewer__toolbar">
        <div class="text-white text-subtitle1 text-weight-medium ellipsis">
          {{ title || 'Fotografía' }}
        </div>
        <q-btn
          flat
          round
          color="white"
          icon="close"
          aria-label="Cerrar fotografía"
          @click="cerrar"
        />
      </div>

      <div class="photo-viewer__body" @click.self="cerrar">
        <img
          v-if="src"
          :src="src"
          :alt="title || 'Fotografía ampliada'"
          class="photo-viewer__image"
        >
        <div v-else class="text-grey-4 text-center">
          <q-icon name="image_not_supported" size="64px" />
          <div class="q-mt-sm">No hay una fotografía disponible.</div>
        </div>
      </div>
    </div>
  </q-dialog>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  src: { type: String, default: '' },
  title: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

function cerrar() {
  emit('update:modelValue', false)
}
</script>

<style scoped>
.photo-viewer {
  min-height: 100vh;
  background: rgba(7, 12, 10, 0.96);
  display: flex;
  flex-direction: column;
}

.photo-viewer__toolbar {
  min-height: 64px;
  padding: 10px 14px 10px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: space-between;
  background: rgba(0, 0, 0, 0.25);
}

.photo-viewer__body {
  flex: 1;
  min-height: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  overflow: auto;
}

.photo-viewer__image {
  display: block;
  max-width: min(96vw, 1200px);
  max-height: calc(100vh - 100px);
  width: auto;
  height: auto;
  object-fit: contain;
  border-radius: 8px;
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.4);
}
</style>
