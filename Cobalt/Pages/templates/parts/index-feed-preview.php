<section class="landing-primary-section landing-main--index-feed-entry {{classes}}">
    <a href="{{page.url_slug.get_path()}}" data-common-tags="{{common_tags}}">
        <img class="landing-main--splash-thumb" loading="lazy" 
        src="{{page.splash_image.filename()}}" 
            style="--accent-color: {{page.splash_image.accent}}">
        <h1>{{page.title}}</h1>
        {{!byline_meta}}
    </a>
    <article class="landing-main--content">
        {{page.summary.display()}}
    </article>
    <a href="{{page.url_slug.get_path()}}" class="button">Read More</a>
</section>