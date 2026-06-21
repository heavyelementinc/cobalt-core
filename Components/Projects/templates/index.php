<section class="main-section" style="margin-bottom: 1em">
    <h1 class="section-title">Our Projects</h1>
    <article>
        <div style="text-align: center; display: flex; flex-direction: column;">
            <p>
                Explore a selection of our recent transformations across Midcoast 
                and Central Maine. From historic coastal exteriors to 
                ultra-smooth interior fine finishes, every project reflects our 
                obsession with preparation, precision, and lasting durability.
            </p>
        </div>
    </article>

    <div class="project-container"  style="margin-top: 1em">
        <div class="project-gallery" style="margin: 0 auto">
            {{!projects}}
        </div>
    </div>
</section>


<section class="main-section cta">
    <h2 class="section-title">Invest in Flawless Craftsmanship</h2>
    <article>
        <p>
            Your home or business deserves more than a standard paint job. From 
            meticulous preparation to the final stroke, we bring a level of care 
            and precision that transforms your space. Get in touch today to 
            schedule your consultation and see the difference true fine 
            finishing makes.
        </p>
    </article>
    <a href="tel:{{app.ContactPublic_phone}}" class="button">
        <i name="phone"></i><?= phone_number_format(__APP_SETTINGS__['PublicContact_phone']) ?>
    </a>
</section>
<style>
    :root {
        --splash-text-color: white;
    }
    #lineup {
        margin-bottom: var(--margin-xxl);
    }
    .customer-quote {
        width: 50%;
    }
    
</style>
