import mjml2html from 'mjml-browser';

window.MauticGrapesJsPreview = {
  render(mjml) {
    const result = mjml2html(mjml, { validationLevel: 'soft' });

    return result?.html || '';
  },
};
