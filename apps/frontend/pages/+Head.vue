<script setup>
import { seo } from '@/utils/seoSettings.js'
</script>

<template>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" :content="seo.meta_description" />
  <meta property="og:type" content="website" />
  <meta property="og:title" :content="seo.meta_title" />
  <meta property="og:description" :content="seo.meta_description" />
  <meta v-if="seo.og_image" property="og:image" :content="seo.og_image" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/assets/main.css" />
  <title>{{ seo.meta_title }}</title>
</template>
