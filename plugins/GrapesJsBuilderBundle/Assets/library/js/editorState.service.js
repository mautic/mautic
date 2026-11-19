export default class EditorStateService {
  setFieldValue;

  setContextReset;

  constructor({ setFieldValue, setContextReset }) {
    this.setFieldValue = setFieldValue;
    this.setContextReset = setContextReset;
  }

  ensureEditorStateField(context) {
    if (!context?.form) {
      return null;
    }

    const existingEditorStateField = context.form.querySelector('textarea.builder-json[name="grapesjsbuilder[editorState]"]');
    if (existingEditorStateField) {
      return existingEditorStateField;
    }

    const field = document.createElement('textarea');
    field.name = 'grapesjsbuilder[editorState]';
    field.id = 'grapesjsbuilder_editorState';
    field.className = 'builder-json hide';
    field.style.display = 'none';
    context.form.appendChild(field);

    return field;
  }

  safeParseEditorState(value) {
    if (!value?.trim()) {
      return null;
    }

    try {
      const parsed = JSON.parse(value);
      return typeof parsed === 'object' && parsed !== null ? parsed : null;
    } catch (error) {
      console.warn('Unable to parse GrapesJS editor state', error);
      return null;
    }
  }

  resolveEditorStateRoute(context) {
    if (!context) {
      return null;
    }

    if (context.editorStateUrl) {
      return context.editorStateUrl;
    }

    if (!context.objectType || !context.entityId) {
      return null;
    }

    const baseUrl = typeof mauticBaseUrl === 'string' && mauticBaseUrl.length > 0 ? mauticBaseUrl : '/';

    let parsedBaseUrl;

    try {
      parsedBaseUrl = new URL(baseUrl, window.location.origin);
    } catch (error) {
      console.warn('Unable to parse mauticBaseUrl when building GrapesJS editor state route', error);
      return null;
    }

    const normalizedBasePath = parsedBaseUrl.pathname.endsWith('/')
      ? parsedBaseUrl.pathname
      : `${parsedBaseUrl.pathname}/`;

    const requestUrl = new URL(
      `s/grapesjsbuilder/${context.objectType}/${context.entityId}/editor-state`,
      `${parsedBaseUrl.origin}${normalizedBasePath}`,
    );

    return requestUrl.toString();
  }

  extractEditorStateFromPayload(payload) {
    if (!payload || typeof payload !== 'object') {
      return null;
    }

    return payload.editorState ?? null;
  }

  applyPrefilledEditorState(editorState, context) {
    if (typeof editorState === 'string') {
      const parsed = this.safeParseEditorState(editorState);
      if (parsed) {
        this.setFieldValue(JSON.stringify(parsed));
        this.setContextReset(context, false);

        return parsed;
      }

      this.setFieldValue(editorState);

      return null;
    }

    if (editorState && typeof editorState === 'object') {
      this.setFieldValue(JSON.stringify(editorState));
      this.setContextReset(context, false);

      return editorState;
    }

    this.setFieldValue('');

    return null;
  }

  async prefillEditorStateField(context) {
    if (!context || context.resetEditorState || !context.entityId) {
      this.setFieldValue('');
      return null;
    }

    const route = this.resolveEditorStateRoute(context);
    if (!route) {
      this.setFieldValue('');
      return null;
    }

    try {
      const response = await fetch(route, {
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        this.setFieldValue('');
        return null;
      }

      const payload = await response.json();
      const editorState = this.extractEditorStateFromPayload(payload);

      return this.applyPrefilledEditorState(editorState, context);
    } catch (error) {
      console.warn('Unable to fetch GrapesJS editor state', error);
      this.setFieldValue('');

      return null;
    }
  }
}