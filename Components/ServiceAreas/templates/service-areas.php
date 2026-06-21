<div id="content" class='white-space other-areas'>
    <h2>We're proud to offer our services to all of Maine!</h2>
    <article>
        {{!custom.delivery_landing.md}}
    </article>
    <div class="columns">
        {{!list}}
    </div>
</div>

<style>
    body > header,
    .page-splash:first-of-type h1 {
        color: <?=($dark) ? "white" : "black"?>;
    }
    #content {
        padding-top: 30vh;
        h2 {
            font-family: 'Barlow Condensed';
            text-transform: none;
            font-size: 1.6em;
            font-weight: bold;
            text-align: center;
        }
    }
    .columns {
        & > div {
            box-sizing: border-box;
            width: 100%;
            position: relative;
        }
        .county-header {
            min-height: 200px;
            padding: var(--margin-m);
            margin: var(--margin-m) 0 var(--margin-s);
            font-size: 2rem;
            position: relative;
            justify-content: center;
            align-items: center;
            display: flex;
            flex-direction: column;
            p {
                font-size: 1rem;
                position: relative;
                max-width: 65ch;
                color: white;
                margin: 0;
                text-align: center;
            }
        }
        h3 {
            font-size: 2rem;
            color: white;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin: 0;
        }
        img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            font-size: 2rem;
            filter: brightness(.5);
        }
        ul {
            margin-bottom: var(--margin-xxl)
        }
    }
    /* .columns {
        columns: 6 200px;
        img {
            object-fit: cover;
            width: 100%;
            height: 200px;
            display: block;
        }
        ul {
            list-style: none;
            padding-top: 0;
        }
        a {
            --color: gray;
        }
    } */
</style>