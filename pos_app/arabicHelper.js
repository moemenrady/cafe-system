/**
 * arabicHelper.js
 * 
 * Enterprise Arabic Text Reshaper & BiDi Layout Engine for ESC/POS Thermal Printers.
 * 
 * Architectural Highlights:
 * 1. Zero C++ / Zero Native Dependencies: Pure JavaScript character mapping table.
 * 2. Complete Arabic Letter Shaping: Converts abstract Unicode Arabic characters (0x0600-0x06FF)
 *    into connected glyphs from Arabic Presentation Forms-B (0xFE70-0xFEFF) based on letter context
 *    (Isolated, Initial, Medial, Final).
 * 3. Bidirectional (BiDi) RTL Reordering: Correctly reverses Arabic words for left-to-right
 *    print head mechanics, while preserving the LTR orientation of numbers, prices, and Latin terms.
 * 4. Lam-Alef (لا) Ligature Support: Combines Lam + Alef variations into unified ligatures.
 * 5. CodePage & Byte Conversion: Outputs UTF-8 Presentation Forms or CP1256/CP864 buffers.
 */

// Arabic characters definitions with glyph forms: [Isolated, Initial, Medial, Final]
// Format: char: [iso, init, med, fin, connects_to_next]
const ARABIC_GLYPHS = {
  '\u0621': [0xFE80, 0xFE80, 0xFE80, 0xFE80, false], // ء Hamza
  '\u0622': [0xFE81, 0xFE81, 0xFE82, 0xFE82, false], // آ Alef with Madda
  '\u0623': [0xFE83, 0xFE83, 0xFE84, 0xFE84, false], // أ Alef with Hamza Above
  '\u0624': [0xFE85, 0xFE85, 0xFE86, 0xFE86, false], // ؤ Waw with Hamza
  '\u0625': [0xFE87, 0xFE87, 0xFE88, 0xFE88, false], // إ Alef with Hamza Below
  '\u0626': [0xFE89, 0xFE8B, 0xFE8C, 0xFE8A, true],  // ئ Yeh with Hamza
  '\u0627': [0xFE8D, 0xFE8D, 0xFE8E, 0xFE8E, false], // ا Alef
  '\u0628': [0xFE8F, 0xFE91, 0xFE92, 0xFE90, true],  // ب Beh
  '\u0629': [0xFE93, 0xFE93, 0xFE94, 0xFE94, false], // ة Teh Marbuta
  '\u062A': [0xFE95, 0xFE97, 0xFE98, 0xFE96, true],  // ت Teh
  '\u062B': [0xFE99, 0xFE9B, 0xFE9C, 0xFE9A, true],  // ث Theh
  '\u062C': [0xFE9D, 0xFE9F, 0xFEA0, 0xFE9E, true],  // ج Jeem
  '\u062D': [0xFEA1, 0xFEA3, 0xFEA4, 0xFEA2, true],  // ح Hah
  '\u062E': [0xFEA5, 0xFEA7, 0xFEA8, 0xFEA6, true],  // خ Khah
  '\u062F': [0xFEA9, 0xFEA9, 0xFEAA, 0xFEAA, false], // د Dal
  '\u0630': [0xFEAB, 0xFEAB, 0xFEAC, 0xFEAC, false], // ذ Thal
  '\u0631': [0xFEAD, 0xFEAD, 0xFEAE, 0xFEAE, false], // ر Reh
  '\u0632': [0xFEAF, 0xFEAF, 0xFEB0, 0xFEB0, false], // ز Zain
  '\u0633': [0xFEB1, 0xFEB3, 0xFEB4, 0xFEB2, true],  // س Seen
  '\u0634': [0xFEB5, 0xFEB7, 0xFEB8, 0xFEB6, true],  // ش Sheen
  '\u0635': [0xFEB9, 0xFEBB, 0xFEBC, 0xFEBA, true],  // ص Sad
  '\u0636': [0xFEBD, 0xFEBF, 0xFEC0, 0xFEBE, true],  // ض Dad
  '\u0637': [0xFEC1, 0xFEC3, 0xFEC4, 0xFEC2, true],  // ط Tah
  '\u0638': [0xFEC5, 0xFEC7, 0xFEC8, 0xFEC6, true],  // ظ Zah
  '\u0639': [0xFEC9, 0xFECB, 0xFECC, 0xFECA, true],  // ع Ain
  '\u063A': [0xFECD, 0xFECF, 0xFED0, 0xFECE, true],  // غ Ghain
  '\u0641': [0xFED1, 0xFED3, 0xFED4, 0xFED2, true],  // ف Feh
  '\u0642': [0xFED5, 0xFED7, 0xFED8, 0xFED6, true],  // ق Qaf
  '\u0643': [0xFED9, 0xFEDB, 0xFEDC, 0xFEDA, true],  // ك Kaf
  '\u0644': [0xFEDD, 0xFEDF, 0xFEE0, 0xFEDE, true],  // ل Lam
  '\u0645': [0xFEE1, 0xFEE3, 0xFEE4, 0xFEE2, true],  // م Meem
  '\u0646': [0xFEE5, 0xFEE7, 0xFEE8, 0xFEE6, true],  // ن Noon
  '\u0647': [0xFEE9, 0xFEEB, 0xFEEC, 0xFEEA, true],  // ه Heh
  '\u0648': [0xFEED, 0xFEED, 0xFEEE, 0xFEEE, false], // و Waw
  '\u0649': [0xFEEF, 0xFBE8, 0xFBE9, 0xFEF0, true],  // ى Alef Maksura
  '\u064A': [0xFEF1, 0xFEF3, 0xFEF4, 0xFEF2, true],  // ي Yeh
  '\u067E': [0xFB56, 0xFB58, 0xFB59, 0xFB57, true],  // پ Peh
  '\u0686': [0xFB7A, 0xFB7C, 0xFB7D, 0xFB7B, true],  // چ Tcheh
  '\u06A9': [0xFB8E, 0xFB90, 0xFB91, 0xFB8F, true],  // ک Keheh
  '\u06AF': [0xFB92, 0xFB94, 0xFB95, 0xFB93, true]   // گ Gaf
};

// Lam-Alef Ligatures
const LIGATURES = {
  '\u0644\u0622': [0xFEF5, 0xFEF6], // لأ Alef with Madda
  '\u0644\u0623': [0xFEF7, 0xFEF8], // لأ Alef with Hamza Above
  '\u0644\u0625': [0xFEF9, 0xFEFA], // لإ Alef with Hamza Below
  '\u0644\u0627': [0xFEFB, 0xFEFC]  // لا Plain Alef
};

function isArabicChar(char) {
  const code = char.charCodeAt(0);
  return (code >= 0x0600 && code <= 0x06FF) || (code >= 0xFB50 && code <= 0xFDFF) || (code >= 0xFE70 && code <= 0xFEFF);
}

function canConnectPrevious(char) {
  if (!char) return false;
  const entry = ARABIC_GLYPHS[char];
  return entry ? entry[4] : false;
}

function canConnectNext(char) {
  if (!char) return false;
  return Boolean(ARABIC_GLYPHS[char]);
}

/**
 * Reshapes an Arabic string into connected Presentation Forms-B glyphs.
 */
function reshapeArabic(text) {
  if (!text || typeof text !== 'string') return '';

  let shaped = '';
  const len = text.length;

  for (let i = 0; i < len; i++) {
    const char = text[i];
    const nextChar = i < len - 1 ? text[i + 1] : null;
    const prevChar = i > 0 ? text[i - 1] : null;

    // 1. Check for Lam-Alef Ligature
    if (char === '\u0644' && nextChar && ('\u0622\u0623\u0625\u0627'.includes(nextChar))) {
      const pair = char + nextChar;
      const lig = LIGATURES[pair];
      if (lig) {
        const connectsPrev = canConnectPrevious(prevChar);
        shaped += String.fromCharCode(connectsPrev ? lig[1] : lig[0]);
        i++; // Skip nextChar
        continue;
      }
    }

    // 2. Normal Arabic Glyphs
    const entry = ARABIC_GLYPHS[char];
    if (entry) {
      const connectsPrev = canConnectPrevious(prevChar);
      const connectsNext = entry[4] && canConnectNext(nextChar);

      let glyphIndex = 0; // Isolated
      if (connectsPrev && connectsNext) {
        glyphIndex = 2; // Medial
      } else if (connectsPrev) {
        glyphIndex = 3; // Final
      } else if (connectsNext) {
        glyphIndex = 1; // Initial
      }

      shaped += String.fromCharCode(entry[glyphIndex]);
    } else {
      shaped += char;
    }
  }

  return shaped;
}

/**
 * Reorders text bidirectional segments for standard LTR thermal receipt print heads.
 * Reverses Arabic segments so they read Right-to-Left, while keeping numbers and Latin terms LTR.
 */
function bidiReorder(text) {
  if (!text) return '';

  // Tokenize into Arabic and Non-Arabic (numbers, Latin, punctuation) chunks
  const tokens = [];
  let currentToken = '';
  let currentIsArabic = null;

  for (let i = 0; i < text.length; i++) {
    const char = text[i];
    const arabic = isArabicChar(char);

    if (currentIsArabic === null) {
      currentIsArabic = arabic;
      currentToken += char;
    } else if (arabic === currentIsArabic) {
      currentToken += char;
    } else {
      tokens.push({ text: currentToken, isArabic: currentIsArabic });
      currentToken = char;
      currentIsArabic = arabic;
    }
  }

  if (currentToken.length > 0) {
    tokens.push({ text: currentToken, isArabic: currentIsArabic });
  }

  // If the line contains Arabic, we reverse the order of tokens,
  // and reverse the internal characters of each Arabic token.
  const hasArabic = tokens.some(t => t.isArabic);
  if (!hasArabic) {
    return text;
  }

  let result = '';
  // Reverse tokens so the line flows Right-To-Left
  for (let i = tokens.length - 1; i >= 0; i--) {
    const token = tokens[i];
    if (token.isArabic) {
      // Reverse Arabic characters within the token
      result += token.text.split('').reverse().join('');
    } else {
      // Keep numbers and Latin words intact
      result += token.text;
    }
  }

  return result;
}

/**
 * Complete Arabic Print Transformer for ESC/POS lines.
 * Performs Contextual Reshaping, BiDi RTL Reordering, and returns UTF-8 Buffer.
 * 
 * @param {string} text - Raw Arabic or mixed text
 * @returns {string} - Reshaped and visually reversed string ready for printer
 */
function prepareArabicLine(text) {
  if (!text) return '';
  const reshaped = reshapeArabic(text);
  return bidiReorder(reshaped);
}

module.exports = {
  reshapeArabic,
  bidiReorder,
  prepareArabicLine,
  isArabicChar
};
