type TranslationModule = Record<string, {
    dictionary: Record<string, string>;
    getPluralForm?: (n: number) => number;
}>;

import afTranslations from 'ckeditor5/translations/af.js';
import arTranslations from 'ckeditor5/translations/ar.js';
import astTranslations from 'ckeditor5/translations/ast.js';
import azTranslations from 'ckeditor5/translations/az.js';
import beTranslations from 'ckeditor5/translations/be.js';
import bgTranslations from 'ckeditor5/translations/bg.js';
import bnTranslations from 'ckeditor5/translations/bn.js';
import bsTranslations from 'ckeditor5/translations/bs.js';
import caTranslations from 'ckeditor5/translations/ca.js';
import csTranslations from 'ckeditor5/translations/cs.js';
import daTranslations from 'ckeditor5/translations/da.js';
import de_chTranslations from 'ckeditor5/translations/de-ch.js';
import deTranslations from 'ckeditor5/translations/de.js';
import elTranslations from 'ckeditor5/translations/el.js';
import en_auTranslations from 'ckeditor5/translations/en-au.js';
import en_gbTranslations from 'ckeditor5/translations/en-gb.js';
import enTranslations from 'ckeditor5/translations/en.js';
import eoTranslations from 'ckeditor5/translations/eo.js';
import es_coTranslations from 'ckeditor5/translations/es-co.js';
import esTranslations from 'ckeditor5/translations/es.js';
import etTranslations from 'ckeditor5/translations/et.js';
import euTranslations from 'ckeditor5/translations/eu.js';
import faTranslations from 'ckeditor5/translations/fa.js';
import fiTranslations from 'ckeditor5/translations/fi.js';
import frTranslations from 'ckeditor5/translations/fr.js';
import glTranslations from 'ckeditor5/translations/gl.js';
import guTranslations from 'ckeditor5/translations/gu.js';
import heTranslations from 'ckeditor5/translations/he.js';
import hiTranslations from 'ckeditor5/translations/hi.js';
import hrTranslations from 'ckeditor5/translations/hr.js';
import huTranslations from 'ckeditor5/translations/hu.js';
import hyTranslations from 'ckeditor5/translations/hy.js';
import idTranslations from 'ckeditor5/translations/id.js';
import itTranslations from 'ckeditor5/translations/it.js';
import jaTranslations from 'ckeditor5/translations/ja.js';
import jvTranslations from 'ckeditor5/translations/jv.js';
import kkTranslations from 'ckeditor5/translations/kk.js';
import kmTranslations from 'ckeditor5/translations/km.js';
import knTranslations from 'ckeditor5/translations/kn.js';
import koTranslations from 'ckeditor5/translations/ko.js';
import kuTranslations from 'ckeditor5/translations/ku.js';
import ltTranslations from 'ckeditor5/translations/lt.js';
import lvTranslations from 'ckeditor5/translations/lv.js';
import msTranslations from 'ckeditor5/translations/ms.js';
import nbTranslations from 'ckeditor5/translations/nb.js';
import neTranslations from 'ckeditor5/translations/ne.js';
import nlTranslations from 'ckeditor5/translations/nl.js';
import noTranslations from 'ckeditor5/translations/no.js';
import ocTranslations from 'ckeditor5/translations/oc.js';
import plTranslations from 'ckeditor5/translations/pl.js';
import pt_brTranslations from 'ckeditor5/translations/pt-br.js';
import ptTranslations from 'ckeditor5/translations/pt.js';
import roTranslations from 'ckeditor5/translations/ro.js';
import ruTranslations from 'ckeditor5/translations/ru.js';
import siTranslations from 'ckeditor5/translations/si.js';
import skTranslations from 'ckeditor5/translations/sk.js';
import slTranslations from 'ckeditor5/translations/sl.js';
import sqTranslations from 'ckeditor5/translations/sq.js';
import sr_latnTranslations from 'ckeditor5/translations/sr-latn.js';
import srTranslations from 'ckeditor5/translations/sr.js';
import svTranslations from 'ckeditor5/translations/sv.js';
import thTranslations from 'ckeditor5/translations/th.js';
import tiTranslations from 'ckeditor5/translations/ti.js';
import tkTranslations from 'ckeditor5/translations/tk.js';
import trTranslations from 'ckeditor5/translations/tr.js';
import ttTranslations from 'ckeditor5/translations/tt.js';
import ugTranslations from 'ckeditor5/translations/ug.js';
import ukTranslations from 'ckeditor5/translations/uk.js';
import urTranslations from 'ckeditor5/translations/ur.js';
import uzTranslations from 'ckeditor5/translations/uz.js';
import viTranslations from 'ckeditor5/translations/vi.js';
import zh_cnTranslations from 'ckeditor5/translations/zh-cn.js';
import zhTranslations from 'ckeditor5/translations/zh.js';

const translations: TranslationModule[] = [
    afTranslations,
    arTranslations,
    astTranslations,
    azTranslations,
    beTranslations,
    bgTranslations,
    bnTranslations,
    bsTranslations,
    caTranslations,
    csTranslations,
    daTranslations,
    de_chTranslations,
    deTranslations,
    elTranslations,
    en_auTranslations,
    en_gbTranslations,
    enTranslations,
    eoTranslations,
    es_coTranslations,
    esTranslations,
    etTranslations,
    euTranslations,
    faTranslations,
    fiTranslations,
    frTranslations,
    glTranslations,
    guTranslations,
    heTranslations,
    hiTranslations,
    hrTranslations,
    huTranslations,
    hyTranslations,
    idTranslations,
    itTranslations,
    jaTranslations,
    jvTranslations,
    kkTranslations,
    kmTranslations,
    knTranslations,
    koTranslations,
    kuTranslations,
    ltTranslations,
    lvTranslations,
    msTranslations,
    nbTranslations,
    neTranslations,
    nlTranslations,
    noTranslations,
    ocTranslations,
    plTranslations,
    pt_brTranslations,
    ptTranslations,
    roTranslations,
    ruTranslations,
    siTranslations,
    skTranslations,
    slTranslations,
    sqTranslations,
    sr_latnTranslations,
    srTranslations,
    svTranslations,
    thTranslations,
    tiTranslations,
    tkTranslations,
    trTranslations,
    ttTranslations,
    ugTranslations,
    ukTranslations,
    urTranslations,
    uzTranslations,
    viTranslations,
    zh_cnTranslations,
    zhTranslations,
];

export const availableTranslationLanguages = new Set([
    'af',
    'ar',
    'ast',
    'az',
    'be',
    'bg',
    'bn',
    'bs',
    'ca',
    'cs',
    'da',
    'de-ch',
    'de',
    'el',
    'en-au',
    'en-gb',
    'en',
    'eo',
    'es-co',
    'es',
    'et',
    'eu',
    'fa',
    'fi',
    'fr',
    'gl',
    'gu',
    'he',
    'hi',
    'hr',
    'hu',
    'hy',
    'id',
    'it',
    'ja',
    'jv',
    'kk',
    'km',
    'kn',
    'ko',
    'ku',
    'lt',
    'lv',
    'ms',
    'nb',
    'ne',
    'nl',
    'no',
    'oc',
    'pl',
    'pt-br',
    'pt',
    'ro',
    'ru',
    'si',
    'sk',
    'sl',
    'sq',
    'sr-latn',
    'sr',
    'sv',
    'th',
    'ti',
    'tk',
    'tr',
    'tt',
    'ug',
    'uk',
    'ur',
    'uz',
    'vi',
    'zh-cn',
    'zh',
]);

export default translations;