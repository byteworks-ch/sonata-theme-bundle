/*
 * Requests to Sonata's endpoints. Sonata treats a request as XHR (layout "ajax", JSON answers) by the header
 * X-Requested-With or the parameter _xml_http_request.
 */
export const XHR_HEADERS = { 'X-Requested-With': 'XMLHttpRequest' };

export async function getHtml(url) {
    const response = await fetch(url, { headers: XHR_HEADERS, credentials: 'same-origin' });

    return response.text();
}

export async function postForm(url, form, submitter = null, accept = 'text/html') {
    const data = submitter ? new FormData(form, submitter) : new FormData(form);
    data.append('_xml_http_request', 'true');

    return fetch(url, {
        method: 'POST',
        body: data,
        headers: { ...XHR_HEADERS, Accept: accept },
        credentials: 'same-origin',
    });
}

export function htmlToElement(html) {
    const template = document.createElement('template');
    template.innerHTML = html.trim();

    return template.content.firstElementChild;
}
