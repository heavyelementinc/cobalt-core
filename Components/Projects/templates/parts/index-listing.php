<a class="project--item" href="/projects/{{doc.url}}" lazy-reveal='stagger'>
    <!-- style="--accent: {{doc.cover_image.getColor()}}; --contrast: {{doc.cover_image.contrast_color}}" -->
    <?= embed_image($doc->cover_image, $doc->cover_image->_id) ?>
    <div class="project--teaser">
        <h2>{{doc.name}}</h2>
        <article>
            {{doc.teaser.md()}}
        </article>
    </div>
    <!-- <img src="{{doc.primary}}" height="400" width="400" alt="{{doc.name}}" lazy-reveal="stagger"> -->
</a>