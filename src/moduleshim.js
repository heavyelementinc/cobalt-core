import EditorJS from "/core-content/js/editorjs/editorjs.mjs";
// import * as Header from "/core-content/js/editorjs/header.js";
import * as LinkTool from "/core-content/js/editorjs/link.js";
// import {RawTool} from "/core-content/js/editorjs/raw.js";
// import {SimpleImage} from "/core-content/js/editorjs/simpleimage.js";
// import {ImageTool} from "/core-content/js/editorjs/imagetool.js";
// // import {Checklist} from "/core-content/js/editorjs/checklist.js";
// import {List} from "/core-content/js/editorjs/list.js";
// // import {Embed} from "/core-content/js/editorjs/embed.js";
// import {Quote} from "/core-content/js/editorjs/quote.js";
// import {CodeTool} from "/core-content/js/editorjs/codetool.js";

// window.Header = Header
console.log(LinkTool);
// window.LinkTool = LinkTool
// window.RawTool = RawTool
// window.SimpleImage = SimpleImage
// window.ImageTool = ImageTool
// // window.Checklist = Checklist
// window.List = List
// // window.Embed = Embed
// window.Quote = Quote
// window.CodeTool = CodeTool
window.EditorJS = EditorJS


// window.addEventListener("cobaltready", async () => {
window.Cobalt.resolvers.editorjs(EditorJS)
// })