import BlockEditor from "./BlockEditor.js";
customElements.define("block-editor", BlockEditor);

import InputPassword from "./InputPassword.js";
customElements.define("input-password", InputPassword);

import InputSwitch from "./InputSwitch.js";
customElements.define("input-switch", InputSwitch);

import MarkdownArea from "./MarkdownArea.js";
customElements.define("markdown-area", MarkdownArea);

import InputRadio from "./InputRadio.js";
customElements.define("input-radio", InputRadio)

import { ObjectGallery, GalleryItem, FileGallery } from "./ObjectGallery.js";
customElements.define("object-gallery", ObjectGallery);
customElements.define("gallery-item", GalleryItem);
customElements.define("file-gallery", FileGallery);

import { ForeignId, FileId } from "./ForeignId.js";
customElements.define("foreign-id", ForeignId);
customElements.define("file-id", FileId);

import ObjectPicker from "./ObjectPicker.js";
customElements.define("object-picker", ObjectPicker);

// This should be the penultimate component to load since we want any components
// that belong to children to be initialized first.
import { default as InputObjectArray, ObjectArrayItem } from "./InputObjectArray.js";
customElements.define("object-array-item", ObjectArrayItem);
customElements.define("input-object-array", InputObjectArray);

// This should always come last since we want all our custom components to be
// ready and declared before the FormRequest initializes.
import FormRequest from "./FormRequest.js"
customElements.define("form-request", FormRequest);